<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Sistema')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    @include('layouts.navbar')

    <div class="container-fluid py-2">
        <main>
            @yield('content')
        </main>
    </div>

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
