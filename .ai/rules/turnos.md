---
paths:
  - 'resources/views/planillas/turnos/**'
---

# Turnos

## Previous final readings become next initial readings
When a new turn is created after saving a turno, the electronic-reading table must preload the previous record's final readings as the new initial values for all hoses. Keep the last saved final reading as the baseline for the next turno and do not require manual re-entry.

## Hose-based open/close planilla was rejected
A flow of opening the planilla by hose and closing it with final readings ran in production from 2026-10-21 to 2026-10-23 (with reading correction and counter change). The company rejected it as not fit for its work, and it was reverted (commits f5daebe and 09b24e7). Do not bring it back without an explicit request. The known risk of the current flow: a new planilla takes the readings of the LAST saved planilla even if that planilla is not finished yet (turno 29 incident of 2026-10-20).
