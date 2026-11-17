{{--
    Carpeta del equipo del administrador donde se guarda el PDF de cada planilla al marcarla como
    revisada (resources/js/pdf-revisado.js). El navegador solo deja elegir la carpeta con un clic.
--}}
<button type="button" {{ $attributes->merge(['class' => 'btn btn-outline-light btn-sm']) }} data-carpeta-planillas
    title="Carpeta donde se guarda el PDF de cada planilla al marcarla como revisada">
    <i class="bi bi-folder2-open" aria-hidden="true"></i>
    <span data-carpeta-planillas-nombre>Carpeta de planillas</span>
</button>
