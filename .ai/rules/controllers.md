---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Inline validation in controllers
Validation is handled directly in controller methods with Request input and shared helpers rather than FormRequest classes. Follow the project’s inline validation style unless a specific need calls for a different pattern.

## Direct Eloquent queries in controllers
This project keeps data access in controllers and uses Eloquent queries directly, including model relationships and raw SQL when needed. Avoid introducing repository or query-object layers unless the task explicitly requires them.
