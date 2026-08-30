---
paths:
  - 'resources/views/planillas/turnos/partials/**'
---

# Partials

## Turno partials must display saved data, and 'fecha' comparisons need whereDate
Every turno partial receives an optional $turno variable and MUST branch on `isset($turno) && optional($turno->relation)->count()` to render already-saved rows (mirror the ventas/surtidores/medios_pago/qr/recaudos partials). lubricantes.blade.php was missing this and silently discarded saved data on every view.

Turno::$casts has 'fecha' => 'date', which Eloquent serializes to a full ISO datetime on save (e.g. "2026-08-29T05:00:00.000000Z"), not a plain "Y-m-d" string. Never compare it with an exact `where('fecha', $string)` — always use `whereDate('fecha', $string)` (see TurnoController@create and @store), or exact-match search silently fails to find the record and can cause duplicate-insert/unique-constraint failures.
