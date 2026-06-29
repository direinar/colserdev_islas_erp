<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="auth-page-wrapper">
    <div class="blobs" aria-hidden="true">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
    </div>

    <div class="auth-card">
        <div class="logo-wrap">
            <div class="logo-ring">
                <x-app-logo-icon class="size-9 fill-current text-white" />
            </div>
            <div class="logo-text">
                <h1>ByH Agrocomercial SAS</h1>
                <p>Acceso a la plataforma de gestión de combustibles</p>
            </div>
        </div>

        <div class="auth-card-body">
            {{ $slot }}
        </div>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
