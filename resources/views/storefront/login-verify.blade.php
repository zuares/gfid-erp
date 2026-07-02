@extends('storefront.layouts.auth')

@section('title', 'Verifikasi OTP — Greatfit')

@push('styles')
<style>
    :root {
        --black: var(--ink);
        --gray-50: var(--soft);
        --gray-100: #f0f0f0;
        --gray-200: #e0e0e0;
        --gray-500: var(--mid);
        --gray-700: #444;
        --radius: 12px;
        --font: var(--font-body);
        --font-condensed: var(--font-display);
    }

    .brand { text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
    .brand-mark { width: 38px; height: 38px; flex-shrink: 0; }
    .brand-name { font-family: var(--font-condensed); font-size: 30px; font-weight: 700; color: var(--black); letter-spacing: -0.5px; line-height: 1; }
    .brand-name span { color: var(--accent-dark); }

    .card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); width: 100%; max-width: 420px; padding: 32px 28px; }

    .icon-wa { width: 52px; height: 52px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 28px; }
    .card-title { font-size: 22px; font-weight: 700; color: var(--black); margin-bottom: 8px; }
    .card-sub { font-size: 14px; color: var(--gray-500); line-height: 1.6; margin-bottom: 24px; }
    .card-sub strong { color: var(--gray-700); }

    .alert { padding: 12px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 20px; }
    .alert-error   { background: #fff0f0; color: #c0392b; border: 1px solid #fad0d0; }
    .alert-success { background: #f0faf3; color: #1a7a43; border: 1px solid #b6e8ca; }

    .otp-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 10px; }
    .otp-boxes { display: flex; gap: 10px; margin-bottom: 22px; }
    .otp-box { flex: 1; height: 58px; border: 2px solid var(--gray-200); border-radius: 8px; font-family: var(--font-condensed); font-size: 28px; font-weight: 700; text-align: center; color: var(--black); background: none; outline: none; transition: border-color .15s; }
    .otp-box:focus { border-color: var(--black); }
    #otp-hidden { display: none; }

    .btn-submit { width: 100%; padding: 14px; background: var(--black); color: var(--white); font-family: var(--font); font-size: 15px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; transition: background .15s; }
    .btn-submit:hover { background: #333; }
    .btn-submit:disabled { background: var(--gray-200); color: var(--gray-500); cursor: not-allowed; }

    .resend-wrap { text-align: center; margin-top: 20px; }
    .resend-text { font-size: 13.5px; color: var(--gray-500); }
    .resend-btn { background: none; border: none; color: var(--black); font-family: var(--font); font-size: 13.5px; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0; }
    #countdown { font-weight: 600; color: var(--gray-700); }

    .footer-link { margin-top: 20px; font-size: 13px; color: var(--gray-500); text-align: center; }
    .footer-link a { color: var(--gray-700); text-decoration: none; font-weight: 500; }
</style>
@endpush

@section('content')
@php
    $displayPhone = '+' . $phone;
    $masked = substr($displayPhone, 0, 7) . str_repeat('*', max(0, strlen($displayPhone) - 10)) . substr($displayPhone, -3);
@endphp

<a href="{{ route('storefront.home') }}" class="brand">
    <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit" class="brand-mark">
    <div class="brand-name">GREAT<span>FIT</span></div>
</a>

<div class="card">
    <div class="icon-wa">💬</div>

    <div class="card-title">Masukkan kode OTP</div>
    <div class="card-sub">
        Kode 6 digit telah dikirim ke WhatsApp<br>
        <strong>{{ $masked }}</strong>. Berlaku 10 menit.
    </div>

    @if(!empty($error))
        <div class="alert alert-error">{{ $error }}</div>
    @endif

    @if(!empty($otpSent) || !empty($resent))
        <div class="alert alert-success">
            {{ $resent ? 'Kode OTP baru sudah dikirim!' : 'Kode OTP terkirim ke WhatsApp kamu.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('storefront.login.verify.post') }}" id="otp-form">
        @csrf
        <div class="otp-label">Kode OTP</div>
        <div class="otp-boxes">
            @for($i = 0; $i < 6; $i++)
                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="{{ $i }}">
            @endfor
        </div>
        <input type="hidden" name="otp" id="otp-hidden">
        <button type="submit" class="btn-submit" id="btn-verify" disabled>Verifikasi</button>
    </form>

    <div class="resend-wrap">
        <span class="resend-text">Belum menerima kode? </span>
        <form method="POST" action="{{ route('storefront.login.resend') }}" id="resend-form" style="display:inline">
            @csrf
            <button type="submit" class="resend-btn" id="resend-btn" disabled>
                Kirim ulang (<span id="countdown">60</span>s)
            </button>
        </form>
    </div>
</div>

<div class="footer-link">
    <a href="{{ route('storefront.login') }}">← Ganti nomor</a>
</div>
@endsection

@push('scripts')
<script>
const boxes = document.querySelectorAll('.otp-box');
const hidden = document.getElementById('otp-hidden');
const btnVerify = document.getElementById('btn-verify');

function updateHidden() {
    const val = Array.from(boxes).map(b => b.value).join('');
    hidden.value = val;
    btnVerify.disabled = val.length < 6;
}

boxes.forEach((box, i) => {
    box.addEventListener('input', e => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        updateHidden();
        if (box.value && i < 5) boxes[i + 1].focus();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            boxes[i - 1].value = '';
            boxes[i - 1].focus();
            updateHidden();
        }
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        text.split('').forEach((ch, j) => { if (boxes[j]) boxes[j].value = ch; });
        updateHidden();
        const nextEmpty = Array.from(boxes).findIndex(b => !b.value);
        if (nextEmpty >= 0) boxes[nextEmpty].focus();
        else boxes[5].focus();
    });
});

boxes[0].focus();

let seconds = 60;
const countEl  = document.getElementById('countdown');
const resendBtn = document.getElementById('resend-btn');

const timer = setInterval(() => {
    seconds--;
    countEl.textContent = seconds;
    if (seconds <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        resendBtn.textContent = 'Kirim ulang';
    }
}, 1000);
</script>
@endpush
