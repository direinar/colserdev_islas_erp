---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/TurnoController.php
---

# Controllers

## Inline validation in controllers
Validation is handled directly in controller methods with Request input and shared helpers rather than FormRequest classes. Follow the project’s inline validation style unless a specific need calls for a different pattern.

## Direct Eloquent queries in controllers
This project keeps data access in controllers and uses Eloquent queries directly, including model relationships and raw SQL when needed. Avoid introducing repository or query-object layers unless the task explicitly requires them.

## Planilla de turnos: a typed 0 is data, and sobrante_pendiente is computed on the server
The save logic lives in GuardarPlanillaTurnoService. The controller only validates (ValidaPlanillaTurno trait), authorizes and delegates. When validation fails, the form is rebuilt with BorradorPlanillaTurnoService, so the typed data is not lost. A planilla row is dropped only when all of its inputs are blank (App\Support\FilaPlanilla::vacia). A typed "0" is saved; the old `valor <= 0` filters made users lose their zeros. sobrante_pendiente is recalculated after each save with CuadreTurnoService::pendiente(), using the same formula as the on-screen resumen and ControlCajaService. Never trust the value the browser posts. An islero may only edit planillas he created (Turno::editablePor, turnos.user_id). The original nombre_vendedor is kept when someone else corrects the planilla. Only an administrador can change the fecha of a saved planilla.
