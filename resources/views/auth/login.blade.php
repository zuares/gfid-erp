<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Greatfit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Barlow+Condensed:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink: #0a0a0a;
            --muted: #6b7280;
            --line: #e5e7eb;
            --soft: #f9fafb;
            --white: #ffffff;
            --danger: #dc2626;
        }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--ink);
            background: var(--white);
            -webkit-font-smoothing: antialiased;
            min-height: 100svh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── LEFT PANEL ── */
        .panel-brand {
            background: var(--ink);
            color: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 48px;
            position: relative;
            overflow: hidden;
        }
        .panel-brand-top {
            display: flex; align-items: center; gap: 10px;
        }
        .brand-wordmark {
            font-size: 13px; font-weight: 900;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--white);
        }
        .panel-brand-mid {
            flex: 1;
            display: flex; align-items: center;
        }
        .brand-big {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(72px, 10vw, 130px);
            font-weight: 900;
            line-height: .88;
            letter-spacing: -.02em;
            text-transform: uppercase;
            color: rgba(255,255,255,.9);
            position: relative; z-index: 1;
        }
        .brand-big-ghost {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(140px, 22vw, 280px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.04em;
            text-transform: uppercase;
            color: rgba(255,255,255,.04);
            position: absolute;
            bottom: -20px; right: -20px;
            user-select: none; pointer-events: none;
        }
        .panel-brand-bottom {
            font-size: 13px;
            color: rgba(255,255,255,.4);
            font-weight: 500;
        }

        /* ── RIGHT PANEL ── */
        .panel-form {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            background: var(--white);
        }
        .form-wrap {
            width: 100%;
            max-width: 360px;
        }
        .form-heading {
            margin-bottom: 32px;
        }
        .form-label-top {
            font-size: 11px; font-weight: 800;
            letter-spacing: .16em; text-transform: uppercase;
            color: var(--muted);
            display: block; margin-bottom: 10px;
        }
        .form-title {
            font-size: 28px; font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.15;
            color: var(--ink);
        }

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 12px; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }
        .field input {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            border: 1.5px solid var(--line);
            border-radius: 6px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; font-weight: 500;
            color: var(--ink);
            background: var(--white);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .field input:focus {
            border-color: var(--ink);
            box-shadow: 0 0 0 3px rgba(10,10,10,.08);
        }
        .field input.is-invalid { border-color: var(--danger); }
        .invalid-msg {
            margin-top: 6px;
            font-size: 12px; font-weight: 600;
            color: var(--danger);
        }

        .field-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .checkbox-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: var(--muted);
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--ink);
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: var(--ink); color: var(--white);
            border: none; border-radius: 6px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px; font-weight: 800;
            letter-spacing: .1em; text-transform: uppercase;
            cursor: pointer;
            transition: opacity .15s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { opacity: .82; }
        .btn-submit:active { opacity: .7; }

        .back-link {
            display: block; text-align: center;
            margin-top: 20px;
            font-size: 13px; font-weight: 600; color: var(--muted);
            text-decoration: none;
            transition: color .15s;
        }
        .back-link:hover { color: var(--ink); }

        .error-global {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 13px; font-weight: 600; color: var(--danger);
        }

        /* ── MOBILE ── */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            .panel-brand {
                padding: 28px 24px;
                min-height: auto;
            }
            .panel-brand-mid { display: none; }
            .brand-big-ghost { display: none; }
            .panel-brand-bottom { display: none; }
            .panel-form {
                padding: 36px 24px;
                align-items: flex-start;
            }
            .form-wrap { max-width: 100%; }
        }
    </style>
</head>
<body>

{{-- LEFT: Brand Panel --}}
<div class="panel-brand">
    <div class="panel-brand-top">
        <img src="{{ asset('images/logo-mark.svg') }}" alt="Greatfit" height="28"
             style="display:block; filter: invert(1) brightness(2);">
        <span class="brand-wordmark">Greatfit</span>
    </div>

    <div class="panel-brand-mid">
        <div>
            <div class="brand-big">
                Good<br>Fit,<br>Good<br>Feel.
            </div>
        </div>
    </div>

    <div class="brand-big-ghost">GF</div>

    <div class="panel-brand-bottom">
        © {{ date('Y') }} Greatfit. All rights reserved.
    </div>
</div>

{{-- RIGHT: Form Panel --}}
<div class="panel-form">
    <div class="form-wrap">
        <div class="form-heading">
            <span class="form-label-top">Admin Panel</span>
            <div class="form-title">Masuk ke<br>Akun Kamu</div>
        </div>

        @if ($errors->any() && !$errors->has('employee_code') && !$errors->has('password'))
            <div class="error-global">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.post', [], false) }}">
            @csrf

            <div class="field">
                <label for="employee_code">Employee Code</label>
                <input type="text" id="employee_code" name="employee_code"
                    value="{{ old('employee_code') }}"
                    class="{{ $errors->has('employee_code') ? 'is-invalid' : '' }}"
                    autofocus autocomplete="username" spellcheck="false">
                @error('employee_code')
                    <div class="invalid-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                    autocomplete="current-password">
                @error('password')
                    <div class="invalid-msg">{{ $message }}</div>
                @enderror
            </div>

            <div class="field-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    Ingat saya
                </label>
            </div>

            <button type="submit" class="btn-submit">
                Masuk
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>

        <a href="{{ route('storefront.home') }}" class="back-link">← Kembali ke Beranda</a>
    </div>
</div>

</body>
</html>
