---
paths:
  - 'app/Services/**'
  - app/Services/KardexInventarioService.php
  - 'app/Services/ControlTanques*.php'
---

# Services

## Business logic belongs in services
Put calculation, import, and orchestration logic in service classes. Controllers should delegate complex operations to services instead of doing business logic inline.

## Fuel price is keyed by numero_turno, not by fecha
A turno's precio_corriente/precio_acpm comes from FuelPrice::precioParaTurno(name, numero_turno): the latest active price with turno_desde <= numero_turno. The price can change between turnos of the same day, so never look it up by fecha. effective_date on fuel_prices is informational only. GuardarPlanillaTurnoService, BorradorPlanillaTurnoService and planillas/turnos/create.blade.php must all use the same lookup. There is no Estado (active) field in the UI any more: a price stops applying when a later one is registered. A price already used by saved planillas (FuelPrice::usadoEnPlanillas) cannot be edited or deleted, and a new price cannot start at or before the last revisada planilla (FuelPriceController::validar).

## Kardex opening balance lives in inventario_saldos_iniciales
Never seed opening stock as a fake Compra: it would show up in the comprobante contable de compras and as an ingreso in Control de tanques. Opening stock is an InventarioSaldoInicial row (inventario gasolina|acpm|canastilla, referencia for canastilla, fecha_corte, unidades, costo_unitario). KardexInventarioService::conSaldoInicial() makes it the first kardex row (tipo 'saldo_inicial') and drops movements dated before fecha_corte. Code that branches on row 'tipo' must handle 'saldo_inicial' (no compra_id, no numero_turno). Tank balances for Control de tanques are a separate table (tanques_saldos_iniciales).

## Control de tanques: FALTANTE/SOBRANTE = saldo final s/tabla de aforo − saldo
The client flipped this formula twice: tabla − saldo until 2026-10-20, saldo − tabla until 2026-10-22, then tabla − saldo again. The final rule, confirmed with a screenshot on 2026-10-22, is faltante_sobrante = saldo final s/tabla de aforo − saldo del movimiento de galones. Do not change it without an explicit request. Positive = sobrante, in black. Negative = faltante, with only the number in red. Since 2026-10-23 there is no FALTANTE word beside the value; instead the word FALTANTE in the column header "FALTANTE/SOBRANTE" is red. This applies on screen (ct-negativo), in the PDF shared to WhatsApp, and in Excel (rich text in G5/M5, normal red-negative format in G and M). Without an aforo for the date the cell stays blank. ACPM follows the same rule and display as corriente.

## CONTEO module (template "PROGRAMACION CONTEO", 2026-10-28)
This is the cash count of the EDS. Only the administrador or gerente records it, whenever they decide (about twice a week), with a chosen date and an automatic consecutive No. It has no review step: once saved it is final. Each table of the template is a submodule (Conteo::SECCIONES, route conteos.seccion), and all formulas live in ConteoService::resumen with the template's numerals. Billetes hold the total per denomination (no multiplication), and FAJOS is a single value. Conteos are chained: the next one takes the last internal consignación No. (ultima_consignacion) and the moneda balance that passes. That is why only the LAST conteo can be modified. The first conteo carries the starting point (consignacion_anterior, saldo_moneda_inicial), like an opening balance. Internal consignaciones are generated from the previous last No.+1 to the typed last No., and each value is looked up in turno_consignaciones by consignacion_no. For that reason the planilla requires the No. (digits only, not repeated in any other planilla) for every consignación with a value. Banks, terceros and destinos are the conteo's own catalogs (conteo_terceros, conteo_destinos).
