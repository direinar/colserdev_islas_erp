---
paths:
  - 'resources/js/galones.js,resources/views/planillas/turnos/**'
---

# Planillas Turnos

## Galones/lecturas numeric format must match between Blade and JS
Galones and lectura_inicial/lectura_final fields use dot-decimal, comma-thousands everywhere (matches Blade's number_format($v, 3, '.', ',')). galones.js's formatGalones() must format with this SAME convention (en-US locale), not es-CO (comma-decimal) — a past mismatch here caused values to look different after a blur-reformat than what Blade renders on reload, and risked 1000x data corruption when parsed server-side. TurnoController::parseDecimal() is bidirectional (handles both dot/comma as decimal) but still relies on this convention being consistent; don't reintroduce a second locale format for these fields.
