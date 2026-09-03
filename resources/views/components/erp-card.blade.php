@props(['title' => null, 'titleClass' => ''])

<style>
    .turno-card-title {
        font-size: 1.25rem;
        font-weight: 700;
    }
</style>

<div class="card erp-card mb-3">

    @if ($title)
        <div class="card-header {{ $titleClass }}">
            {{ $title }}
        </div>
    @endif

    <div class="card-body p-0">

        {{ $slot }}

    </div>

</div>
