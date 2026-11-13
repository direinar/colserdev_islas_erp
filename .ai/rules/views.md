---
paths:
  - 'resources/views/**'
---

# Views

## Flux UI is the default UI system
This project standardizes on Flux UI components for interface elements. Prefer Flux components over ad hoc HTML when building or updating views, and match the surrounding component style.

## Use wire:navigate for links
Navigation links use wire:navigate to keep the app in a single-page interaction flow. Match this pattern on links and menu items instead of forcing full page reloads.

## Bootstrap and Tailwind coexist by component
Bootstrap 5 and Tailwind CSS 4 are both currently used in the application. Do not remove, migrate, or replace either framework automatically. When modifying an existing component, preserve the styling approach already used by that component and prefer consistency with the surrounding code. Do not introduce Tailwind into an existing Bootstrap component unless explicitly requested, and do not introduce Bootstrap into an existing Tailwind component unless explicitly requested. For new UI, first inspect nearby components and existing project conventions before choosing Bootstrap or Tailwind. Any future migration from Bootstrap to Tailwind must be an explicit, planned task and must not happen as part of unrelated feature development.

## Aviso global de información sin guardar en formularios POST
resources/js/aviso-sin-guardar.js (importado en app.js) avisa al salir de la página si un form method="post" tiene cambios digitados por el usuario (solo eventos isTrusted: los 'input' que disparan galones.js y otros scripts no cuentan). No agregar beforeunload por pantalla. Para excluir un formulario use el atributo data-sin-aviso (ya en los de pages/auth); los wire:submit y los GET no se vigilan. form.submit() desde código también limpia el estado (se parcheó HTMLFormElement.prototype.submit). Para una confirmación propia use window.avisoSinGuardar.hayCambios(form) y .descartar() (ver saldo inicial en control-tanques). Requiere npm run build y subir public/build.
