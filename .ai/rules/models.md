---
paths:
  - 'app/Models/**'
---

# Models

## Use $fillable and $casts properties
Model mass-assignment and type casting are defined with protected $fillable and protected $casts. Match this existing pattern in models instead of introducing casts() or Attribute-based accessors.

## Explicit $table for non-standard pluralization
When a model name does not map cleanly to a table name, declare protected $table explicitly. This project uses explicit table names for inventory models to avoid mispluralized Eloquent table names.
