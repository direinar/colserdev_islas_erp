---
paths:
  - 'resources/views/planillas/turnos/**'
---

# Turnos

## Previous final readings become next initial readings
When a new turn is created after saving a turno, the electronic-reading table must preload the previous record's final readings as the new initial values for all hoses. Keep the last saved final reading as the baseline for the next turno and do not require manual re-entry.

## Hose-based open/close planilla was rejected
A flow of opening the planilla by hose and closing it with final readings ran in production from 2026-10-21 to 2026-10-23 (with reading correction and counter change). The company rejected it as not fit for its work, and it was reverted (commits f5daebe and 09b24e7). Do not bring it back without an explicit request. The known risk of the current flow: a new planilla takes the readings of the LAST saved planilla even if that planilla is not finished yet (turno 29 incident of 2026-10-20).

## Electronic reading is recorded by date, not in the planilla (2026-10-23)
Since the "cambio a planilla de turnos" document of 2026-10-23, the planilla has no LECTURA ELECTRÓNICA, no VENTAS/SURTIDORES column in the summary and no "SOBRANTE o FALTANTE x LECTURA SURTIDORES". The electronic reading is a daily submodule under Operación (LecturaElectronicaController, LecturaElectronicaService, table lecturas_electronicas). Its sections are (A) the sale per readings, (B) the day's sales per IAPROPIADA closings (sum of the date's turnos), and (C) = A − B. The initial reading of a day is the final of the previous recorded day, falling back to the last turno_surtidores reading. Correcting a day's final updates the next day's initial. Saving the planilla no longer touches the turno_surtidores of old turnos. INFORMACIÓN DE VENTAS DEL TURNO has one block per surtidor (TurnoVenta::SURTIDORES) with + AGREGAR FILA and a NO. CIERRE column (turno_ventas.no_cierre). Rows without a closing number and without gallons are not saved.
