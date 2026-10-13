---
paths:
  - 'app/Services/**'
---

# Services

## Business logic belongs in services
Put calculation, import, and orchestration logic in service classes. Controllers should delegate complex operations to services instead of doing business logic inline.

## Fuel price is keyed by numero_turno, not by fecha
A turno's precio_corriente/precio_acpm comes from FuelPrice::precioParaTurno(name, numero_turno): the latest active price with turno_desde <= numero_turno. The price can change between turnos of the same day, so never look it up by fecha. effective_date on fuel_prices is informational only. GuardarPlanillaTurnoService, BorradorPlanillaTurnoService and planillas/turnos/create.blade.php must all use the same lookup.
