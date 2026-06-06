<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Gasolinera - Planilla de Turnos' }}</title>

    @livewireStyles

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:        #0d0d0d;
            --surface:   #141414;
            --surface2:  #1c1c1c;
            --surface3:  #242424;
            --border:    #2e2e2e;
            --border2:   #3a3a3a;
            --amber:     #f59e0b;
            --amber2:    #fbbf24;
            --amber-dim: #78450a;
            --green:     #22c55e;
            --green-dim: #14532d;
            --red:       #ef4444;
            --red-dim:   #7f1d1d;
            --blue:      #3b82f6;
            --blue-dim:  #1e3a5f;
            --text:      #e5e5e5;
            --text-dim:  #737373;
            --text-muted:#4b4b4b;
            --mono:      'IBM Plex Mono', monospace;
            --sans:      'IBM Plex Sans Condensed', sans-serif;
            --radius:    3px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--mono);
            font-size: 12px;
            line-height: 1.4;
            min-height: 100vh;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--surface); }
        ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }

        /* ── Top bar ── */
        .topbar {
            background: var(--surface);
            border-bottom: 2px solid var(--amber);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-logo {
            font-family: var(--sans);
            font-size: 18px;
            font-weight: 700;
            color: var(--amber);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .topbar-logo span { color: var(--text-dim); font-weight: 400; }
        .topbar-sep { flex: 1; }
        .topbar-badge {
            background: var(--amber-dim);
            color: var(--amber);
            border: 1px solid var(--amber);
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            border-radius: var(--radius);
        }

        /* ── Main wrapper ── */
        .main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ── Section card ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 12px;
            overflow: hidden;
        }
        .card-header {
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            padding: 7px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--amber);
            flex-shrink: 0;
        }
        .card-title {
            font-family: var(--sans);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--amber);
        }
        .card-body { padding: 12px 14px; }

        /* ── Grid layouts ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

        /* ── Header info row ── */
        .header-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto 1fr;
            gap: 16px;
            align-items: end;
            padding: 14px 20px;
            background: var(--surface);
            border-bottom: 2px solid var(--border);
            margin-bottom: 12px;
        }
        .header-field { display: flex; flex-direction: column; gap: 4px; }
        .field-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-dim);
        }

        /* ── Inputs ── */
        input[type="text"],
        input[type="date"],
        input[type="number"] {
            background: var(--surface3);
            border: 1px solid var(--border2);
            border-radius: var(--radius);
            color: var(--text);
            font-family: var(--mono);
            font-size: 12px;
            padding: 5px 8px;
            width: 100%;
            transition: border-color .15s;
            outline: none;
        }
        input:focus {
            border-color: var(--amber);
            background: #1e1a10;
        }
        input[readonly] {
            background: var(--surface2);
            color: var(--green);
            border-color: var(--border);
            cursor: default;
        }
        input.valor-computed {
            color: var(--amber2);
            font-weight: 600;
        }

        /* ── Table ── */
        .t {
            width: 100%;
            border-collapse: collapse;
        }
        .t th {
            background: var(--surface2);
            color: var(--text-dim);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid var(--border);
            text-align: left;
            white-space: nowrap;
        }
        .t td {
            border: 1px solid var(--border);
            padding: 2px 4px;
            vertical-align: middle;
        }
        .t tr:hover td { background: rgba(245,158,11,0.04); }

        .t td input {
            border: none;
            background: transparent;
            border-radius: 0;
            padding: 4px 6px;
        }
        .t td input:focus {
            background: #1e1a10;
            border-radius: var(--radius);
        }

        .t .label-cell {
            color: var(--text-dim);
            font-size: 11px;
            padding: 4px 8px;
            white-space: nowrap;
        }
        .t .tipo-badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
        .tipo-cte  { background: var(--blue-dim);  color: #93c5fd; }
        .tipo-acpm { background: var(--green-dim); color: #86efac; }

        /* ── Totals row ── */
        .t .total-row td {
            background: var(--surface2);
            border-top: 1px solid var(--amber-dim);
            font-weight: 600;
            color: var(--amber2);
            padding: 5px 8px;
        }
        .t .total-row .label-cell { color: var(--amber); }

        /* ── Summary panel ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .summary-item {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .summary-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-dim);
        }
        .summary-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--amber2);
            font-family: var(--mono);
        }
        .summary-value.positive { color: var(--green); }
        .summary-value.negative { color: var(--red); }
        .summary-value.neutral  { color: var(--text); }

        /* ── Diff row ── */
        .diff-positive { color: var(--green); font-weight: 700; }
        .diff-negative { color: var(--red);   font-weight: 700; }

        /* ── Buttons ── */
        .btn {
            font-family: var(--mono);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all .15s;
            text-transform: uppercase;
        }
        .btn-primary {
            background: var(--amber);
            color: #000;
        }
        .btn-primary:hover { background: var(--amber2); }
        .btn-primary:disabled {
            background: var(--amber-dim);
            color: var(--text-dim);
            cursor: not-allowed;
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-dim);
            border: 1px solid var(--border2);
        }
        .btn-ghost:hover { border-color: var(--text-dim); color: var(--text); }

        /* ── Alert ── */
        .alert {
            padding: 10px 16px;
            border-radius: var(--radius);
            font-size: 12px;
            font-weight: 600;
        }
        .alert-success {
            background: var(--green-dim);
            border: 1px solid var(--green);
            color: var(--green);
        }
        .alert-error {
            background: var(--red-dim);
            border: 1px solid var(--red);
            color: var(--red);
        }

        /* ── Inline total tag ── */
        .inline-total {
            background: var(--surface3);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--amber2);
            font-weight: 700;
            padding: 4px 8px;
            text-align: right;
            white-space: nowrap;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 10px 0;
        }

        /* ── Two-column section layout ── */
        .cols-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        .cols-layout-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 900px) {
            .cols-layout, .cols-layout-3, .summary-grid { grid-template-columns: 1fr; }
            .header-row { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-logo">⛽ GasStation <span>/ Sistema de Turnos</span></div>
    <div class="topbar-sep"></div>
    <div class="topbar-badge">OPERATIVO</div>
</div>

<div class="main">
    {{ $slot }}
</div>

@livewireScripts
</body>
</html>
