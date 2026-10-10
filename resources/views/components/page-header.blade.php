@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

{{--
    Encabezado común de los módulos: título (único <h1> de la página), descripción opcional
    y, a la derecha, el contenido del slot (filtros, botones de imprimir/Excel, "Nuevo…").
    Al imprimir se ve como texto plano y las acciones se ocultan.
--}}
<header {{ $attributes->merge(['class' => 'page-header mb-3']) }}>
    <div class="page-header-texto">
        <h1 class="page-header-titulo">
            @if ($icon)
                <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
            @endif
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="page-header-descripcion">{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim($slot) !== '')
        <div class="page-header-acciones d-print-none">
            {{ $slot }}
        </div>
    @endif
</header>
