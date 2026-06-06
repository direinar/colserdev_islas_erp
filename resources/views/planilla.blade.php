<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @livewireStyles
    <title>{{ __('Planilla de islas') }} - {{ config('app.name', 'Laravel') }}</title>
</head>

<body class="min-h-screen bg-slate-950">
    <livewire:planilla />

    @livewireScripts
    @fluxScripts
</body>

</html>
