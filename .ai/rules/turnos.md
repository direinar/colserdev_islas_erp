---
paths:
  - 'resources/views/planillas/turnos/**'
---

# Turnos

## Previous final readings become next initial readings
When a new turn is created after saving a turno, the electronic-reading table must preload the previous record's final readings as the new initial values for all hoses. Keep the last saved final reading as the baseline for the next turno and do not require manual re-entry.
