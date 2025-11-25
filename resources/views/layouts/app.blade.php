{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'GFID'))</title>

    {{-- Bootstrap 5 CDN (boleh diganti @vite) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- THEME + GLOBAL STYLES --}}
    @include('layouts.partials.styles')

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

                    @if ($errors->any())
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
</body>

</html>
