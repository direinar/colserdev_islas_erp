<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <div class="card-dot"></div>
        <div class="card-title">{{ $title ?? ($slotTitle ?? '') }}</div>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
<div class="card shadow-sm border-0">

    <div class="card-header bg-white">

        <strong>
            {{ $title }}
        </strong>

    </div>

    <div class="card-body">

        {{ $slot }}

    </div>

</div>
