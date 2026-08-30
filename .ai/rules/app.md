---
paths:
  - 'app/**'
---

# App

## Strict PHP typing and explicit signatures
Methods throughout the codebase use explicit return types and parameter types. Keep typed method signatures and avoid untyped PHP methods unless the codebase explicitly calls for a local exception.

## Project-wide naming conventions
Use the project naming pattern: models are singular PascalCase, controllers are [Resource]Controller, DTOs are [Entity]DTO, services are [Action]Service, and actions are [Action]Action. Route names use dot notation, database tables are snake_case, and Blade view paths follow kebab-case directories.

## Laravel default conventions follow project reality
This application follows its actual code patterns rather than introducing new framework defaults. Preserve project conventions already in use, especially for validation style, model patterns, and UI framework choices, instead of adopting new patterns without a task-specific reason.
