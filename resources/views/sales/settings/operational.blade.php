@extends('layouts.app')

@section('title', 'Pengaturan Operasional Penjualan')

@push('head')
<style>
.sos-page { max-width: 980px; display: grid; gap: 1rem; }
.sos-head h1 { margin: 0; font-size: 1.35rem; font-weight: 900; color: #0f172a; }
.sos-head p { margin: .3rem 0 0; color: #64748b; font-size: .84rem; }
.sos-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem 1.35rem; box-shadow: 0 8px 24px rgba(15,23,42,.04); }
.sos-card-head { display: flex; align-items: center; justify-content: space-between; gap: .8rem; }
.sos-title { font-size: .98rem; font-weight: 900; color: #0f172a; }
.sos-sub { margin-top: .25rem; color: #64748b; font-size: .78rem; line-height: 1.5; }
.sos-status { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .25rem .65rem; font-size: .72rem; font-weight: 850; white-space: nowrap; }
.sos-status.is-on { color: #166534; background: #dcfce7; }
.sos-status.is-off { color: #92400e; background: #fef3c7; }
.sos-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; padding: .85rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
.sos-row-label { color: #334155; font-size: .82rem; font-weight: 800; }
.sos-row-help { margin-top: .15rem; color: #94a3b8; font-size: .7rem; }
.sos-toggle { min-width: 118px; min-height: 36px; border: 1px solid #cbd5e1; border-radius: 999px; padding: .35rem .8rem; background: #fff; color: #475569; font-size: .76rem; font-weight: 850; cursor: pointer; }
.sos-toggle.is-on { border-color: #16a34a; background: #16a34a; color: #fff; }
.sos-toggle.is-off { border-color: #f59e0b; background: #fff7ed; color: #9a3412; }
.sos-actions { display: flex; justify-content: flex-end; gap: .55rem; margin-top: .9rem; }
.sos-button { border: 0; border-radius: 8px; padding: .5rem .8rem; background: #0f172a; color: #fff; font-size: .74rem; font-weight: 850; cursor: pointer; }
.sos-button-light { border: 1px solid #cbd5e1; background: #fff; color: #475569; }
.sos-map { display: grid; gap: .9rem; margin-top: 1.25rem; }
.sos-group { display: grid; gap: .45rem; }
.sos-group-title { font-size: .63rem; font-weight: 900; color: #94a3b8; letter-spacing: .1em; text-transform: uppercase; }
.sos-event { display: grid; grid-template-columns: minmax(180px,.8fr) minmax(220px,1.2fr); gap: .75rem; align-items: center; padding: .65rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
.sos-event-label { font-size: .75rem; font-weight: 800; color: #334155; }
.sos-event-help { margin-top: .1rem; font-size: .64rem; color: #94a3b8; }
.sos-select { width: 100%; min-height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0 .55rem; background: #fff; color: #334155; font-size: .74rem; font-weight: 700; }
.sos-box { margin-top: 1rem; padding: 1rem; border: 1px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; }
.sos-box-title { font-size: .77rem; font-weight: 900; color: #334155; }
.sos-upload-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: .6rem; align-items: end; margin-top: .7rem; }
.sos-input, .sos-file { width: 100%; min-height: 34px; border: 1px solid #e2e8f0; border-radius: 8px; padding: .35rem .5rem; background: #fff; color: #475569; font-size: .72rem; }
.sos-library { display: grid; gap: .45rem; margin-top: .7rem; }
.sos-library-row { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .5rem .65rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
.sos-library-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #334155; font-size: .73rem; font-weight: 800; }
.sos-library-meta { margin-top: .1rem; color: #94a3b8; font-size: .62rem; }
.sos-delete { border: 1px solid #fecaca; border-radius: 7px; padding: .25rem .5rem; background: #fff; color: #b91c1c; font-size: .67rem; font-weight: 800; cursor: pointer; }
.sos-trim { border-color: #cbd5e1; color: #334155; }
.sos-note { margin-top: .9rem; padding: .7rem .8rem; border: 1px solid #dbeafe; border-radius: 10px; background: #eff6ff; color: #1e40af; font-size: .72rem; line-height: 1.5; }
.sos-choice-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; margin-top: .8rem; }
.sos-choice { position: relative; }
.sos-choice input { position: absolute; opacity: 0; pointer-events: none; }
.sos-choice label { display: block; height: 100%; padding: .7rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #475569; cursor: pointer; }
.sos-choice label strong { display: block; color: #334155; font-size: .75rem; }
.sos-choice label span { display: block; margin-top: .18rem; color: #94a3b8; font-size: .65rem; line-height: 1.4; }
.sos-choice input:checked + label { border-color: #334155; background: #f1f5f9; box-shadow: 0 0 0 2px rgba(51,65,85,.08); }
.sos-setting-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-top: .9rem; }
.sos-check { display: flex; align-items: flex-start; gap: .5rem; padding: .65rem .7rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; }
.sos-check input { margin-top: .12rem; accent-color: #334155; }
.sos-check strong { display: block; color: #334155; font-size: .73rem; }
.sos-check span { display: block; margin-top: .12rem; color: #94a3b8; font-size: .64rem; line-height: 1.4; }
.sos-field-label { display: block; margin-top: .9rem; color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
.sos-doc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.sos-doc-frame { width: 100%; height: 350px; margin-top: .7rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; }
.sos-doc-actions { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .7rem; }
@media(max-width:640px){ .sos-card { padding: 1rem; } .sos-card-head, .sos-row { align-items: flex-start; flex-direction: column; } .sos-toggle, .sos-actions, .sos-button { width: 100%; } .sos-event, .sos-upload-grid { grid-template-columns: 1fr; } .sos-library-row { align-items: flex-start; flex-direction: column; } audio { width: 100% !important; } }
@media(max-width:760px){ .sos-choice-grid, .sos-setting-grid, .sos-doc-grid { grid-template-columns: 1fr; } .sos-doc-frame { height: 430px; } }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="sos-page">
        <div class="sos-head">
            <h1>⚙️ Pengaturan Operasional Penjualan</h1>
            <p>Konfigurasi scan pengiriman, ringtone, dan feedback kerja operator.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-0">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mb-0">❌ {{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('sales.settings.operational.update') }}" enctype="multipart/form-data" id="sos-form">
            @csrf
            <input type="hidden" name="shipment_scan_sound_enabled" id="sos-sound-value" value="{{ $soundEnabled ? '1' : '0' }}">

            <div class="sos-card">
                <div class="sos-card-head">
                    <div>
                        <div class="sos-title">Suara Scan Pengiriman</div>
                        <div class="sos-sub">Atur default suara dan mapping ringtone untuk setiap event scanner.</div>
                    </div>
                    <span class="sos-status {{ $soundEnabled ? 'is-on' : 'is-off' }}" id="sos-status">{{ $soundEnabled ? '● Aktif' : '● Nonaktif' }}</span>
                </div>

                <div class="sos-row">
                    <div>
                        <div class="sos-row-label">Suara feedback scan</div>
                        <div class="sos-row-help">Berlaku sebagai default global seluruh operator.</div>
                    </div>
                    <button type="button" id="sos-toggle" class="sos-toggle {{ $soundEnabled ? 'is-on' : 'is-off' }}">{{ $soundEnabled ? '🔊 Suara ON' : '🔇 Suara OFF' }}</button>
                </div>

                <div class="sos-actions">
                    <button type="submit" name="reset_shipment_scan_defaults" value="1" id="sos-reset" class="sos-button sos-button-light">Reset Default GFID</button>
                    <button type="submit" class="sos-button">Simpan Pengaturan</button>
                </div>

                <div class="sos-map">
                    <div class="sos-title" style="font-size:.82rem">Mapping suara per event</div>
                    @foreach(collect($soundEvents)->groupBy('group') as $group => $events)
                    <div class="sos-group">
                        <div class="sos-group-title">{{ $group }}</div>
                        @foreach($events as $event)
                        <div class="sos-event">
                            <div>
                                <div class="sos-event-label">{{ $event['label'] }}</div>
                                <div class="sos-event-help">{{ $event['help'] }}</div>
                            </div>
                            <select name="shipment_scan_sound_map[{{ $event['key'] }}]" class="sos-select">
                                <optgroup label="Suara bawaan">
                                    @foreach($builtinSounds as $builtinKey => $builtinLabel)
                                    <option value="builtin:{{ $builtinKey }}" {{ ($soundMap[$event['key']] ?? '') === 'builtin:' . $builtinKey ? 'selected' : '' }}>{{ $builtinLabel }}</option>
                                    @endforeach
                                </optgroup>
                                @if($ringtones->isNotEmpty())
                                <optgroup label="Library ringtone">
                                    @foreach($ringtones as $ringtone)
                                    <option value="ringtone:{{ $ringtone->id }}" {{ ($soundMap[$event['key']] ?? '') === 'ringtone:' . $ringtone->id ? 'selected' : '' }}>{{ $ringtone->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>

                <div class="sos-box">
                    <div class="sos-box-title">Tambah ringtone ke library</div>
                    <div style="margin-top:.2rem;color:#94a3b8;font-size:.67rem;line-height:1.45">MP3, WAV, OGG, M4A, AAC, FLAC, atau WEBM · maksimal 20 MB · dipotong maksimal 5 detik dan dikompres menjadi MP3 mono 64 kbps.</div>
                    <div class="sos-upload-grid">
                        <div>
                            <label class="sos-event-help" for="sos-name">Nama ringtone</label>
                            <input type="text" name="shipment_scan_ringtone_name" id="sos-name" class="sos-input" placeholder="Contoh: Sukses 01">
                        </div>
                        <div>
                            <label class="sos-event-help" for="sos-audio">File audio</label>
                            <input type="file" name="shipment_scan_ringtone_audio" id="sos-audio" class="sos-file" accept="audio/*">
                        </div>
                        <button type="submit" name="shipment_scan_ringtone_upload" value="1" class="sos-button">Upload & Kompres</button>
                    </div>
                </div>

                @if($ringtones->isNotEmpty())
                <div class="sos-box">
                    <div class="sos-box-title">Library ringtone tersimpan</div>
                    <div class="sos-library">
                        @foreach($ringtones as $ringtone)
                        <div class="sos-library-row">
                            <div style="min-width:0">
                                <div class="sos-library-name" title="{{ $ringtone->original_name }}">{{ $ringtone->name }}</div>
                                <div class="sos-library-meta">{{ number_format(($ringtone->compressed_size_bytes ?? 0) / 1024, 1, ',', '.') }} KB @if($ringtone->duration_ms) · {{ number_format($ringtone->duration_ms / 1000, 1, ',', '.') }} detik @endif · {{ $ringtone->original_name }}</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.4rem">
                                <audio controls preload="none" style="width:150px;height:28px" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ringtone->path) }}"></audio>
                                <button type="button" class="sos-delete sos-trim" data-trim-id="{{ $ringtone->id }}" data-trim-name="{{ $ringtone->name }}" data-trim-duration="{{ (float) (($ringtone->duration_ms ?? 0) / 1000) }}">Potong</button>
                                <button type="button" class="sos-delete" data-ringtone-id="{{ $ringtone->id }}" data-ringtone-name="{{ $ringtone->name }}">Hapus</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="sos-note">Default GFID mengaktifkan suara dan mengembalikan mapping ke preset bawaan. Ringtone custom yang sudah di-upload tidak dihapus.</div>
            </div>
        </form>

        <div class="sos-card">
            <div class="sos-card-head">
                <div>
                    <div class="sos-title">Lookup &amp; Tautkan Order</div>
                    <div class="sos-sub">Atur kapan order boleh dicari ke modul marketplace atau invoice.</div>
                </div>
                <span class="sos-status {{ $lookupSettings['lookup_mode'] === 'record_only' ? 'is-off' : 'is-on' }}">
                    {{ $lookupSettings['lookup_mode'] === 'record_only' ? '● Record-only' : '● Lookup aktif' }}
                </span>
            </div>

            <form method="POST" action="{{ route('sales.settings.operational.update') }}" id="sos-lookup-form">
                @csrf
                <div class="sos-field-label">Mode kerja order</div>
                <div class="sos-choice-grid">
                    @foreach([
                        'record_only' => ['Pencatatan order', 'Scan tetap aktif dan tersimpan, tanpa lookup atau tautan otomatis.'],
                        'suggest_on_confirm' => ['Lookup saat konfirmasi', 'Scan order dicatat dulu; lookup dilakukan saat operator menekan proses di halaman confirm.'],
                        'auto_link_on_scan' => ['Tautkan saat scan', 'Scan langsung mencari dan menautkan order jika data sumber tersedia.'],
                    ] as $mode => [$label, $help])
                        <div class="sos-choice">
                            <input type="radio" name="sales_lookup_mode" id="sales-mode-{{ $mode }}" value="{{ $mode }}" {{ $lookupSettings['lookup_mode'] === $mode ? 'checked' : '' }}>
                            <label for="sales-mode-{{ $mode }}"><strong>{{ $label }}</strong><span>{{ $help }}</span></label>
                        </div>
                    @endforeach
                </div>

                <div class="sos-field-label">Sumber lookup</div>
                <div class="sos-setting-grid">
                    <label class="sos-check">
                        <input type="checkbox" name="sales_lookup_sources[]" value="marketplace_order" {{ in_array('marketplace_order', $lookupSettings['lookup_sources'], true) ? 'checked' : '' }}>
                        <span><strong>Marketplace Order</strong><span>Channel order, AWB, booking, dan external order.</span></span>
                    </label>
                    <label class="sos-check">
                        <input type="checkbox" name="sales_lookup_sources[]" value="sales_invoice" {{ in_array('sales_invoice', $lookupSettings['lookup_sources'], true) ? 'checked' : '' }}>
                        <span><strong>Sales Invoice</strong><span>Kode invoice dan nomor order pada invoice internal.</span></span>
                    </label>
                </div>

                <div class="sos-field-label">Identitas yang boleh dicari</div>
                <div class="sos-setting-grid">
                    @foreach([
                        'shipping_awb_no' => ['No. resi / AWB', 'shipping_awb_no'],
                        'channel_order_id' => ['Channel order ID', 'channel_order_id'],
                        'booking_sn' => ['Booking SN', 'booking_sn'],
                        'external_order_id' => ['External order ID', 'external_order_id'],
                        'invoice_code' => ['Kode invoice', 'invoice_code'],
                        'channel_invoice_no' => ['Channel invoice no', 'channel_invoice_no'],
                    ] as $identifier => [$label, $help])
                        <label class="sos-check">
                            <input type="checkbox" name="sales_lookup_identifiers[]" value="{{ $identifier }}" {{ in_array($identifier, $lookupSettings['lookup_identifiers'], true) ? 'checked' : '' }}>
                            <span><strong>{{ $label }}</strong><span>{{ $help }}</span></span>
                        </label>
                    @endforeach
                </div>

                <div class="sos-setting-grid">
                    <label class="sos-check"><input type="hidden" name="sales_lookup_same_store" value="0"><input type="checkbox" name="sales_lookup_same_store" value="1" {{ $lookupSettings['same_store'] ? 'checked' : '' }}><span><strong>Batasi ke store yang sama</strong><span>Mencegah order dari toko lain tertaut ke shipment.</span></span></label>
                    <label class="sos-check"><input type="hidden" name="sales_lookup_block_duplicate" value="0"><input type="checkbox" name="sales_lookup_block_duplicate" value="1" {{ $lookupSettings['block_duplicate'] ? 'checked' : '' }}><span><strong>Blokir order aktif ganda</strong><span>Order yang masih diproses di shipment lain ditolak.</span></span></label>
                    <label class="sos-check"><input type="hidden" name="sales_allow_unlinked_submit" value="0"><input type="checkbox" name="sales_allow_unlinked_submit" value="1" {{ $lookupSettings['allow_unlinked_submit'] ? 'checked' : '' }}><span><strong>Boleh submit record-only</strong><span>Order tetap dapat dicatat dan stok diproses tanpa tautan marketplace.</span></span></label>
                    <label class="sos-check"><input type="hidden" name="sales_allow_mixed_linkage" value="0"><input type="checkbox" name="sales_allow_mixed_linkage" value="1" {{ $lookupSettings['allow_mixed_linkage'] ? 'checked' : '' }}><span><strong>Boleh campur linked &amp; record-only</strong><span>Nonaktifkan untuk menjaga satu shipment tetap konsisten.</span></span></label>
                    <label class="sos-check"><input type="hidden" name="sales_record_only_daily_sales" value="0"><input type="checkbox" name="sales_record_only_daily_sales" value="1" {{ $lookupSettings['record_only_daily_sales'] ? 'checked' : '' }}><span><strong>Catat Daily Sales untuk record-only</strong><span>Default OFF agar pencatatan belum dianggap penjualan terkonfirmasi.</span></span></label>
                </div>

                <div class="sos-field-label" for="sales-marketplace-status-timing">Update status marketplace</div>
                <select class="sos-select" id="sales-marketplace-status-timing" name="sales_marketplace_status_timing">
                    <option value="never" {{ $lookupSettings['status_timing'] === 'never' ? 'selected' : '' }}>Jangan update otomatis</option>
                    <option value="on_link" {{ $lookupSettings['status_timing'] === 'on_link' ? 'selected' : '' }}>Saat order ditautkan</option>
                    <option value="on_post" {{ $lookupSettings['status_timing'] === 'on_post' ? 'selected' : '' }}>Saat shipment diposting (recommended)</option>
                </select>

                <div class="sos-actions">
                    <button type="submit" name="reset_sales_operational_defaults" value="1" id="sos-reset-lookup" class="sos-button sos-button-light">Reset Default Lookup</button>
                    <button type="submit" class="sos-button">Simpan Lookup</button>
                </div>
            </form>
            <div class="sos-note">Record-only tetap bisa dipakai untuk scan dan pencatatan order. Pilih “Lookup saat konfirmasi” jika order perlu dicari dan ditautkan setelah proses scan selesai.</div>
        </div>

        <div class="sos-card">
            <div class="sos-card-head">
                <div>
                    <div class="sos-title">Dokumen Scanner</div>
                    <div class="sos-sub">Preview dan cetak panduan scanner serta barcode kontrol operasional.</div>
                </div>
            </div>
            <div class="sos-doc-grid">
                @foreach($scanDocuments as $document)
                    @php $documentUrl = route('sales.settings.operational.documents', $document['key']); @endphp
                    <div class="sos-box" style="margin-top:1rem">
                        <div class="sos-box-title">{{ $document['title'] }}</div>
                        <div class="sos-sub">{{ $document['description'] }} · {{ $document['pages'] }} halaman</div>
                        <iframe class="sos-doc-frame" src="{{ $documentUrl }}#toolbar=1&navpanes=0&view=FitH" title="Preview {{ $document['title'] }}"></iframe>
                        <div class="sos-doc-actions">
                            <a class="sos-button sos-button-light" href="{{ $documentUrl }}" target="_blank" rel="noopener">Preview penuh</a>
                            <button type="button" class="sos-button" data-print-pdf="{{ $documentUrl }}">Cetak PDF</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('sos-form');
    const soundValue = document.getElementById('sos-sound-value');
    const toggle = document.getElementById('sos-toggle');
    const status = document.getElementById('sos-status');
    const reset = document.getElementById('sos-reset');
    const deleteUrl = @json(route('sales.settings.operational.ringtones.delete', '__RINGTONE__'));
    const trimUrl = @json(route('sales.settings.operational.ringtones.trim', '__RINGTONE__'));
    const csrf = @json(csrf_token());

    function renderSound(enabled) {
        soundValue.value = enabled ? '1' : '0';
        toggle.classList.toggle('is-on', enabled);
        toggle.classList.toggle('is-off', !enabled);
        toggle.textContent = enabled ? '🔊 Suara ON' : '🔇 Suara OFF';
        status.classList.toggle('is-on', enabled);
        status.classList.toggle('is-off', !enabled);
        status.textContent = enabled ? '● Aktif' : '● Nonaktif';
    }
    toggle?.addEventListener('click', () => renderSound(soundValue.value !== '1'));

    reset?.addEventListener('click', function (event) {
        if (this.dataset.confirmed === '1') return;
        event.preventDefault();
        const submit = () => { this.dataset.confirmed = '1'; form.requestSubmit(this); };
        if (!window.Swal) { if (window.confirm('Kembalikan semua mapping ke default GFID?')) submit(); return; }
        window.Swal.fire({ icon:'question', title:'Reset default suara?', text:'Mapping kembali ke preset bawaan dan suara diaktifkan.', showCancelButton:true, confirmButtonText:'Ya, reset', cancelButtonText:'Batal', confirmButtonColor:'#0f172a' }).then(result => { if (result.isConfirmed) submit(); });
    });

    document.querySelectorAll('[data-ringtone-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const name = this.dataset.ringtoneName || 'ringtone ini';
            const confirmed = window.Swal
                ? (await window.Swal.fire({ icon:'warning', title:'Hapus ringtone?', text:name, showCancelButton:true, confirmButtonText:'Hapus', cancelButtonText:'Batal', confirmButtonColor:'#b91c1c' })).isConfirmed
                : window.confirm('Hapus ' + name + '?');
            if (!confirmed) return;
            try {
                const response = await fetch(deleteUrl.replace('__RINGTONE__', encodeURIComponent(this.dataset.ringtoneId)), {
                    method:'POST',
                    headers:{
                        'Accept':'application/json',
                        'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With':'XMLHttpRequest',
                        'X-CSRF-TOKEN':csrf
                    },
                    body: new URLSearchParams({ _token: csrf, _method: 'DELETE' })
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.status !== 'ok') {
                    throw new Error(payload.message || `Ringtone gagal dihapus (HTTP ${response.status}).`);
                }
                window.location.reload();
            } catch (error) {
                if (window.GFID?.errorAlert) window.GFID.errorAlert(error.message || 'Ringtone gagal dihapus.');
                else window.alert(error.message || 'Ringtone gagal dihapus.');
            }
        });
    });

    document.querySelectorAll('[data-trim-id]').forEach((button) => {
        button.addEventListener('click', async function () {
            const name = this.dataset.trimName || 'ringtone ini';
            const currentDuration = Number(this.dataset.trimDuration || 0);
            let values = null;

            if (window.Swal) {
                const result = await window.Swal.fire({
                    icon: 'info',
                    title: 'Potong durasi ringtone',
                    text: `${name}${currentDuration ? ` · durasi saat ini ${currentDuration.toFixed(2)} detik` : ''}`,
                    html: `<div style="text-align:left;display:grid;gap:8px;margin-top:10px">
                        <label style="font-size:12px;font-weight:700">Mulai dari detik</label>
                        <input id="sos-trim-start" class="swal2-input" type="number" min="0" step="0.1" value="0" style="margin:0;width:100%">
                        <label style="font-size:12px;font-weight:700">Durasi hasil (detik)</label>
                        <input id="sos-trim-duration" class="swal2-input" type="number" min="0.1" max="30" step="0.1" value="${Math.min(currentDuration || 5, 5)}" style="margin:0;width:100%">
                    </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Potong & Simpan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#0f172a',
                    preConfirm: () => {
                        const start = Number(document.getElementById('sos-trim-start')?.value || 0);
                        const duration = Number(document.getElementById('sos-trim-duration')?.value || 0);
                        if (!Number.isFinite(start) || start < 0 || !Number.isFinite(duration) || duration <= 0) {
                            window.Swal.showValidationMessage('Isi mulai dan durasi dengan angka yang valid.');
                            return null;
                        }
                        return { start, duration };
                    }
                });
                if (!result.isConfirmed) return;
                values = result.value;
            } else {
                const start = Number(window.prompt('Mulai dari detik', '0'));
                const duration = Number(window.prompt('Durasi hasil dalam detik', String(Math.min(currentDuration || 5, 5))));
                if (!Number.isFinite(start) || !Number.isFinite(duration) || start < 0 || duration <= 0) return;
                values = { start, duration };
            }

            try {
                const response = await fetch(trimUrl.replace('__RINGTONE__', encodeURIComponent(this.dataset.trimId)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: new URLSearchParams({
                        _token: csrf,
                        trim_start: String(values.start),
                        trim_duration: String(values.duration),
                    }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.status !== 'ok') {
                    throw new Error(payload.message || `Ringtone gagal dipotong (HTTP ${response.status}).`);
                }
                window.location.reload();
            } catch (error) {
                if (window.GFID?.errorAlert) window.GFID.errorAlert(error.message || 'Ringtone gagal dipotong.');
                else window.alert(error.message || 'Ringtone gagal dipotong.');
            }
        });
    });

    document.querySelectorAll('[data-print-pdf]').forEach((button) => {
        button.addEventListener('click', function () {
            const printWindow = window.open(this.dataset.printPdf, '_blank', 'noopener,noreferrer');
            if (!printWindow) {
                if (window.GFID?.errorAlert) window.GFID.errorAlert('Popup diblokir browser. Izinkan popup untuk mencetak PDF.');
                return;
            }
            printWindow.focus();
            let printed = false;
            const triggerPrint = () => {
                if (printed) return;
                printed = true;
                try { printWindow.print(); } catch (error) {}
            };
            printWindow.addEventListener?.('load', () => window.setTimeout(triggerPrint, 250));
            window.setTimeout(triggerPrint, 1500);
        });
    });

    const resetLookup = document.getElementById('sos-reset-lookup');
    const lookupForm = document.getElementById('sos-lookup-form');
    resetLookup?.addEventListener('click', function (event) {
        if (this.dataset.confirmed === '1') return;
        event.preventDefault();
        const submit = () => { this.dataset.confirmed = '1'; lookupForm.requestSubmit(this); };
        if (!window.Swal) { if (window.confirm('Kembalikan pengaturan lookup ke default aman?')) submit(); return; }
        window.Swal.fire({ icon:'question', title:'Reset default lookup?', text:'Mode kembali ke record-only dan status marketplace diubah saat posting.', showCancelButton:true, confirmButtonText:'Ya, reset', cancelButtonText:'Batal', confirmButtonColor:'#0f172a' }).then(result => { if (result.isConfirmed) submit(); });
    });
})();
</script>
@endpush
