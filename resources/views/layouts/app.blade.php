<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Sistema')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.pwa')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>

    @include('layouts.navbar')

    <div class="container-fluid py-2">
        <main>
            @yield('content')
            {{-- Páginas Livewire de página completa (settings/*) se renderizan en $slot --}}
            {{ $slot ?? '' }}
        </main>
    </div>

    @fluxScripts
</body>

</html>



{{-- <body>

    @include('layouts.navbar')

    <div class="container-fluid">
        <div class="row">

            @include('layouts.sidebar')

            <main class="col-md-10 ms-sm-auto px-4 py-4">

                @yield('content')

            </main>

        </div>
    </div>

    @include('layouts.footer')

</body>

</html> --}}
