@extends('storefront.layouts.checkout')

@section('title', 'Alamat Pengiriman — Greatfit')

@section('nav-right')
<a href="{{ $returnTo }}" style="font-size:11px;font-weight:800;color:var(--mid);">Kembali</a>
@endsection

@push('styles')
<style>
    :root { --red: #e53935; }
    body { padding-bottom: calc(76px + var(--safe)); }
    .wrap { width: min(720px, calc(100% - 28px)); margin: 0 auto; }

    .head { padding: 18px 0 14px; border-bottom: 1px solid var(--line); margin-bottom: 16px; }
    .title { font-size: 15px; font-weight: 900; }
    .sub { font-size: 12px; color: var(--mid); font-weight: 600; margin-top: 4px; line-height: 1.5; }
    .panel { border: 1px solid var(--line); border-radius: 18px; padding: 16px; margin-bottom: 14px; background: var(--white); }
    .panel-title { font-size: 11px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; margin-bottom: 12px; }
    .field { margin-bottom: 12px; }
    .field:last-child { margin-bottom: 0; }
    label { display: block; font-size: 11px; font-weight: 900; color: #333; margin-bottom: 7px; }
    input, select, textarea {
        width: 100%;
        border: 1px solid var(--line);
        background: var(--soft);
        border-radius: 12px;
        min-height: 44px;
        padding: 0 12px;
        color: var(--ink);
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        outline: none;
    }
    textarea { min-height: 88px; padding-top: 12px; resize: vertical; line-height: 1.5; }
    select:disabled { color: #999; background: #fafafa; }
    input:focus, select:focus, textarea:focus { border-color: var(--ink); background: var(--white); }
    .grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
    .error { color: var(--red); font-size: 11px; font-weight: 700; margin-top: 6px; }
    .hint { font-size: 11px; color: var(--mid); font-weight: 600; line-height: 1.5; margin-top: 8px; }
    .save-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 90; padding: 12px 14px calc(12px + var(--safe)); background: rgba(255,255,255,.97); backdrop-filter: blur(14px); border-top: 1px solid var(--line); box-shadow: 0 -12px 30px rgba(0,0,0,.08); }
    .save-inner { width: min(720px, calc(100% - 28px)); margin: 0 auto; display: flex; gap: 10px; }
    .btn { height: 46px; border-radius: 14px; border: 0; font: inherit; font-size: 13px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .btn-secondary { flex: .8; background: var(--soft); color: var(--ink); border: 1px solid var(--line); }
    .btn-primary { flex: 1.2; background: var(--ink); color: var(--white); box-shadow: 0 10px 24px rgba(0,0,0,.16); }

    @@media (min-width: 720px) {
        body { padding-bottom: 0; background: var(--soft); }
        .head { padding: 28px 0 18px; margin-bottom: 20px; }
        .title { font-size: 20px; }
        .panel { padding: 22px; border-radius: 18px; }
        .grid { grid-template-columns: 1fr 1fr; }
        .save-bar { position: static; box-shadow: none; border-top: 0; background: transparent; padding: 8px 0 40px; }
        .save-inner { justify-content: flex-end; width: min(720px, calc(100% - 28px)); }
        .btn { flex: 0 0 auto; min-width: 160px; }
    }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="head">
        <div class="title">Alamat Pengiriman</div>
        <div class="sub">Isi alamat dengan dropdown wilayah otomatis agar pengiriman lebih mudah diproses.</div>
    </div>

    <form method="POST" action="{{ route('storefront.checkout.address.save') }}" id="address-form">
        @csrf
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
        <input type="hidden" name="province_name" id="province-name" value="{{ old('province_name', $address['province_name'] ?? '') }}">
        <input type="hidden" name="city_name" id="city-name" value="{{ old('city_name', $address['city_name'] ?? '') }}">
        <input type="hidden" name="district_name" id="district-name" value="{{ old('district_name', $address['district_name'] ?? '') }}">
        <input type="hidden" name="village_name" id="village-name" value="{{ old('village_name', $address['village_name'] ?? '') }}">

        <section class="panel">
            <div class="panel-title">Kontak Penerima</div>
            <div class="grid">
                <div class="field">
                    <label for="recipient_name">Nama penerima</label>
                    <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $address['recipient_name'] ?? '') }}" placeholder="Nama lengkap" required>
                    @error('recipient_name')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="phone">Nomor HP</label>
                    <input id="phone" name="phone" value="{{ old('phone', $address['phone'] ?? '') }}" inputmode="tel" placeholder="08xxxxxxxxxx" required>
                    @error('phone')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-title">Wilayah</div>
            <div class="grid">
                <div class="field">
                    <label for="province">Provinsi</label>
                    <select id="province" name="province_id" required><option value="">Memuat provinsi...</option></select>
                    @error('province_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="city">Kota/Kabupaten</label>
                    <select id="city" name="city_id" required disabled><option value="">Pilih provinsi dulu</option></select>
                    @error('city_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="district">Kecamatan</label>
                    <select id="district" name="district_id" required disabled><option value="">Pilih kota dulu</option></select>
                    @error('district_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="village">Kelurahan/Desa</label>
                    <select id="village" name="village_id" required disabled><option value="">Pilih kecamatan dulu</option></select>
                    @error('village_id')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="hint">Data wilayah dimuat otomatis dari API wilayah Indonesia.</div>
        </section>

        <section class="panel">
            <div class="panel-title">Detail Alamat</div>
            <div class="field">
                <label for="detail">Alamat lengkap</label>
                <textarea id="detail" name="detail" placeholder="Nama jalan, nomor rumah, RT/RW, patokan" required>{{ old('detail', $address['detail'] ?? '') }}</textarea>
                @error('detail')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="grid">
                <div class="field">
                    <label for="postal_code">Kode pos</label>
                    <input id="postal_code" name="postal_code" value="{{ old('postal_code', $address['postal_code'] ?? '') }}" inputmode="numeric" placeholder="Opsional">
                    @error('postal_code')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="note">Catatan kurir</label>
                    <input id="note" name="note" value="{{ old('note', $address['note'] ?? '') }}" placeholder="Opsional">
                    @error('note')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div class="save-bar">
            <div class="save-inner">
                <a href="{{ $returnTo }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Alamat</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@php
    $initialRegion = [
        'province_id'  => old('province_id',  $address['province_id']  ?? ''),
        'city_id'      => old('city_id',      $address['city_id']      ?? ''),
        'district_id'  => old('district_id',  $address['district_id']  ?? ''),
        'village_id'   => old('village_id',   $address['village_id']   ?? ''),
    ];
@endphp
<script>
(function() {
    var apiBase = 'https://www.emsifa.com/api-wilayah-indonesia/api';
    var initial = @json($initialRegion);

    var selects = {
        province: document.getElementById('province'),
        city: document.getElementById('city'),
        district: document.getElementById('district'),
        village: document.getElementById('village')
    };
    var names = {
        province: document.getElementById('province-name'),
        city: document.getElementById('city-name'),
        district: document.getElementById('district-name'),
        village: document.getElementById('village-name')
    };

    function endpoint(path) { return apiBase + '/' + path; }

    function setLoading(select, text) {
        select.innerHTML = '<option value="">' + text + '</option>';
        select.disabled = true;
    }

    function fillOptions(select, rows, placeholder, selectedValue) {
        select.innerHTML = '<option value="">' + placeholder + '</option>' + rows.map(function(row) {
            var selected = String(row.id) === String(selectedValue) ? ' selected' : '';
            return '<option value="' + row.id + '"' + selected + '>' + row.name + '</option>';
        }).join('');
        select.disabled = false;
        syncName(select);
    }

    async function loadOptions(select, url, placeholder, selectedValue) {
        setLoading(select, 'Memuat...');
        try {
            var response = await fetch(url);
            var rows = await response.json();
            fillOptions(select, rows, placeholder, selectedValue);
        } catch (error) {
            select.innerHTML = '<option value="">Gagal memuat data</option>';
            select.disabled = true;
        }
    }

    function syncName(select) {
        var selected = select.options[select.selectedIndex];
        if (!selected) return;
        var key = select.id;
        names[key].value = select.value ? selected.textContent : '';
    }

    function resetBelow(level) {
        if (level === 'province') {
            setLoading(selects.city, 'Pilih provinsi dulu');
            setLoading(selects.district, 'Pilih kota dulu');
            setLoading(selects.village, 'Pilih kecamatan dulu');
            names.city.value = ''; names.district.value = ''; names.village.value = '';
        }
        if (level === 'city') {
            setLoading(selects.district, 'Pilih kota dulu');
            setLoading(selects.village, 'Pilih kecamatan dulu');
            names.district.value = ''; names.village.value = '';
        }
        if (level === 'district') {
            setLoading(selects.village, 'Pilih kecamatan dulu');
            names.village.value = '';
        }
    }

    selects.province.addEventListener('change', function() {
        syncName(this); resetBelow('province');
        if (this.value) loadOptions(selects.city, endpoint('regencies/' + this.value + '.json'), 'Pilih kota/kabupaten', '');
    });
    selects.city.addEventListener('change', function() {
        syncName(this); resetBelow('city');
        if (this.value) loadOptions(selects.district, endpoint('districts/' + this.value + '.json'), 'Pilih kecamatan', '');
    });
    selects.district.addEventListener('change', function() {
        syncName(this); resetBelow('district');
        if (this.value) loadOptions(selects.village, endpoint('villages/' + this.value + '.json'), 'Pilih kelurahan/desa', '');
    });
    selects.village.addEventListener('change', function() { syncName(this); });

    async function boot() {
        await loadOptions(selects.province, endpoint('provinces.json'), 'Pilih provinsi', initial.province_id);
        if (initial.province_id) await loadOptions(selects.city, endpoint('regencies/' + initial.province_id + '.json'), 'Pilih kota/kabupaten', initial.city_id);
        if (initial.city_id) await loadOptions(selects.district, endpoint('districts/' + initial.city_id + '.json'), 'Pilih kecamatan', initial.district_id);
        if (initial.district_id) await loadOptions(selects.village, endpoint('villages/' + initial.district_id + '.json'), 'Pilih kelurahan/desa', initial.village_id);
    }

    boot();
})();
</script>
@endpush
