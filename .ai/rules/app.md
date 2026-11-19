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

## Tres módulos: Operaciones, Nómina (/nomina) y Contabilidad (/contabilidad)
Desde 2026-11-19 la nómina vive dentro del ERP. Código de nómina en carpetas Nomina/ (Controllers, Models, Services, factories, tests/Feature/Nomina), vistas en resources/views/nomina, rutas con prefijo /nomina y nombres nomina.*, tablas nomina_*. App\Models\Nomina\Turno (turno de trabajo) NO es App\Models\Turno (planilla de turno de la EDS). Permisos: User::rolesOperaciones() (admin, jefe de patios, islero), User::rolesNomina() (solo superadmin, gerente y administrador; también Contabilidad; el rol contador existe pero sin ningún módulo); User::modulos() alimenta el selector de la barra. No crear public/nomina: chocaría con la ruta /nomina. MySQL admite nombres de índice de máximo 64 caracteres (SQLite de las pruebas no avisa): poner nombre corto a unique/index compuestos.
