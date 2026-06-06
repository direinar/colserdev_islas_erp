<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Planilla de turnos') }} - {{ config('app.name', 'Laravel') }}</title>
    @livewireStyles

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bg: #0d0d0d;
            --surface: #141414;
            --surface2: #1c1c1c;
            --surface3: #242424;
            --border: #2e2e2e;
            --border2: #3a3a3a;
            --amber: #f59e0b;
            --amber2: #fbbf24;
            --amber-dim: #78450a;
            --green: #22c55e;
            --green-dim: #14532d;
            --red: #ef4444;
            --red-dim: #7f1d1d;
            --blue: #3b82f6;
            --blue-dim: #1e3a5f;
            --text: #e5e5e5;
            --text-dim: #737373;
            --text-muted: #4b4b4b;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'IBM Plex Sans Condensed', sans-serif;
            --radius: 3px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--mono);
            font-size: 12px;
            line-height: 1.4;
            min-height: 100vh;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border2);
            border-radius: 3px;
        }

        .main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="main">
        <livewire:planilla-turno />
    </div>

    @livewireScripts
</body>

</html>
