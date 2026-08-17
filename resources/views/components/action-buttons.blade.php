@props(['showRoute' => null, 'editRoute' => null, 'deleteRoute' => null, 'id' => null])
<div class="crud-actions">
    @if ($showRoute)
        <a href="{{ route($showRoute, $id) }}" class="btn btn-action view">Ver</a>
    @endif

    @if ($editRoute)
        <a href="{{ route($editRoute, $id) }}" class="btn btn-action edit">Editar</a>
    @endif

    @if ($deleteRoute)
        <form method="POST" action="{{ route($deleteRoute, $id) }}"
            onsubmit="return confirm('¿Eliminar este elemento?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-action delete">Eliminar</button>
        </form>
    @endif
</div>
