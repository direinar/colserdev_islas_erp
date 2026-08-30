---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Class-based Livewire components
Livewire components in this project are class-based and live in app/Livewire/ with separate Blade views. Keep this pattern and avoid switching to single-file Livewire components unless the task explicitly calls for it.

## Public properties define component state
Component state is defined as public properties on the Livewire class. Match this pattern instead of relying on attribute-based validation or non-public state. Keep server-side state in public properties for this project’s components.

## Computed properties use Property suffix
Computed values are exposed as getters with the Property suffix, such as getTotalGalonesProperty(). Use the same computed-property pattern for Livewire values rather than creating separate accessor patterns.
