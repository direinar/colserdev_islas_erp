---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/TurnoController.php
  - app/Http/Controllers/CompraController.php
---

# Controllers

## Inline validation in controllers
Validation is handled directly in controller methods with Request input and shared helpers rather than FormRequest classes. Follow the project’s inline validation style unless a specific need calls for a different pattern.

## Direct Eloquent queries in controllers
This project keeps data access in controllers and uses Eloquent queries directly, including model relationships and raw SQL when needed. Avoid introducing repository or query-object layers unless the task explicitly requires them.

## Planilla de turnos: a typed 0 is data, and sobrante_pendiente is computed on the server
The save logic lives in GuardarPlanillaTurnoService. The controller only validates (ValidaPlanillaTurno trait), authorizes and delegates. When validation fails, the form is rebuilt with BorradorPlanillaTurnoService, so the typed data is not lost. A planilla row is dropped only when all of its inputs are blank (App\Support\FilaPlanilla::vacia). A typed "0" is saved; the old `valor <= 0` filters made users lose their zeros. sobrante_pendiente is recalculated after each save with CuadreTurnoService::pendiente(), using the same formula as the on-screen resumen and ControlCajaService. Never trust the value the browser posts. Islero and jefe de patios may only edit planillas they created (Turno::editablePor, turnos.user_id); an islero only sees his own and a jefe de patios sees his own plus the isleros' (Turno::visiblePara and its scope, used for the búsqueda and the list by fecha). "Administrador" checks (isAdministrador, User::rolesAdministrativos) include gerente and superadmin; only they (User::rolesControlTotal) manage gerente/superadmin accounts. The original nombre_vendedor is kept when someone else corrects the planilla. Only an administrador can change the fecha of a saved planilla.

## Compras de combustible: the cost is liquidated, never typed
Since the "MODULO COMPRAS ADICIONADO IVA" template, per fuel the admin types galones, base19, base5, costo_producto, sobretasa and soldicom. LiquidacionCompraCombustibleService computes iva = base19*19% + base5*5%, distribucion_{fuel} (= COSTO GASOLINA / COSTO ACPM, formerly "distrib. costo") = iva + producto + sobretasa + soldicom, vr_total_fra and total. Never accept distribucion_* or vr_total_fra from the request. Kardex and the comprobante de compras keep reading distribucion_* / vr_total_fra, so they need no change. Costo unitario is not stored (Compra::costoUnitario). The entry row markup lives in compras/partials/fila.blade.php and is reused as the <template> for new rows.

## New planilla: the number is assigned when "Nuevo" is opened, so it can collide on save
Several users create planillas at the same time (2026-10-28). The form of a new planilla posts planilla_nueva=1. If its numero_turno was taken by someone else in the meantime, TurnoController@store saves it with the next free number (and retries on a unique-key collision) and tells the user. Never treat a "Nuevo" form as an edit of the existing planilla. Before this, the administrador silently OVERWROTE the other user's planilla (it looked "deleted"), and the other roles got "registered by another user" and lost what they typed. An edit, meaning a planilla opened from the search, has no planilla_nueva and updates the same number.
