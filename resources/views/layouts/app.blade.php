<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'GFID'))</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- Global reset ringan untuk konsistensi Chrome Android & iOS --}}
    <style>
        html {
            box-sizing: border-box;
            -webkit-text-size-adjust: 100%;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
        }

        input,
        select,
        textarea {
            font-size: 16px;
            /* cegah auto-zoom di Chrome iOS */
        }
    </style>

    {{-- THEME + GLOBAL STYLES ASLI MU --}}
    @include('layouts.partials.styles')
    <link rel="stylesheet" href="{{ asset('css/light-minimal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-high-contrast.css') }}"> {{-- override dark --}}

    {{-- ✅ Tambahan: ganjel konten di atas bottom nav khusus mobile --}}
    <style>
        @media (max-width: 767.98px) {
            .app-main .page-wrap {
                padding-bottom: 9rem;
                /* > 62px tinggi bottom nav, jadi ada jarak */
            }
        }
    </style>

    @stack('head')
</head>


<body>
    <div id="app" class="d-flex flex-column min-vh-100">

        {{-- NAVBAR --}}
        @include('layouts.partials.navbar')

        {{-- MOBILE SIDEBAR (drawer) --}}
        @include('layouts.partials.mobile-sidebar')

        {{-- APP SHELL: sidebar + main --}}
        <div class="app-shell">
            {{-- SIDEBAR DESKTOP --}}
            @include('layouts.partials.sidebar')

            {{-- MAIN CONTENT --}}
            <main class="app-main py-3">
                <div class="page-wrap">

                    {{-- Flash message simple --}}
                    @if (session('success'))
                        <div class="alert alert-success mb-3">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger mb-3">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Error validasi (pakai ViewErrorBag) --}}
                    @php
                        $hasValidationErrors =
                            $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors->any() : false;
                    @endphp

                    @if ($hasValidationErrors)
                        <div class="alert alert-danger mb-3">
                            <strong>Terjadi error:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
            {{-- APP FOOTER (kalau nanti mau ditambah) --}}
        </div>

        {{-- BOTTOM NAV MOBILE --}}
        @auth
            @include('layouts.partials.mobile-bottom-nav')
        @endauth
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    {{-- THEME TOGGLER SCRIPT --}}
    @include('layouts.partials.theme-script')

    @stack('scripts')

    <script>
        // Disable double-tap zoom (iOS Safari)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>

</body>

</html>
