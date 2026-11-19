{{--
    Pestañas de la Auditoría del administrador y estilos del semáforo.
    Parámetros: $activa ('informe' | 'eventos'), $porGestionar (fallas y advertencias sin cerrar), $desde, $hasta.
--}}
<style>
    .aud-pestanas { display: flex; gap: .25rem; border-bottom: 2px solid var(--bs-border-color); margin-bottom: 1rem; flex-wrap: wrap; }
    .aud-pestanas a { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem 1rem; border-radius: .5rem .5rem 0 0;
        color: #495057; font-weight: 600; text-decoration: none; margin-bottom: -2px; border-bottom: 3px solid transparent; }
    .aud-pestanas a:hover { background: #f1f3f5; }
    .aud-pestanas a.activa { color: #0d6efd; border-bottom-color: #0d6efd; background: #fff; }

    .semaforo-punto { display: inline-block; width: .8rem; height: .8rem; border-radius: 50%; flex-shrink: 0; vertical-align: middle; }
    .semaforo-rojo { --sem: #dc3545; --sem-suave: #fdecee; }
    .semaforo-amarillo { --sem: #e0a800; --sem-suave: #fff8e1; }
    .semaforo-verde { --sem: #198754; --sem-suave: #e8f5ee; }
    .semaforo-gris { --sem: #6c757d; --sem-suave: #f1f3f5; }
    .semaforo-punto.semaforo-rojo, .semaforo-punto.semaforo-amarillo, .semaforo-punto.semaforo-verde, .semaforo-punto.semaforo-gris {
        background: var(--sem); box-shadow: 0 0 0 3px var(--sem-suave); }
    .semaforo-rojo.semaforo-punto { animation: semaforo-latido 1.6s ease-in-out infinite; }
    @keyframes semaforo-latido { 50% { box-shadow: 0 0 0 6px var(--sem-suave); } }

    .semaforo-insignia { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px;
        font-size: .75rem; font-weight: 700; background: var(--sem-suave); color: var(--sem); border: 1px solid var(--sem); white-space: nowrap; }

    @media print {
        .aud-pestanas { display: none; }
        .semaforo-rojo.semaforo-punto { animation: none; }
    }
</style>

<nav class="aud-pestanas d-print-none" aria-label="Secciones de la auditoría">
    <a href="{{ route('control-planillas.index', ['desde' => $desde, 'hasta' => $hasta]) }}" @class(['activa' => $activa === 'informe'])>
        <i class="bi bi-clipboard-data" aria-hidden="true"></i> Informe de auditoría
    </a>
    <a href="{{ route('control-planillas.eventos.index', ['desde' => $desde, 'hasta' => $hasta]) }}" @class(['activa' => $activa === 'eventos'])>
        <i class="bi bi-activity" aria-hidden="true"></i> Fallas y eventos de la aplicación
        @if ($porGestionar > 0)
            <span class="badge rounded-pill text-bg-danger">{{ $porGestionar }}</span>
        @endif
    </a>
</nav>
