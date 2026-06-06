<button {{ $attributes->merge([
    'class' => 'btn btn-' . $color
]) }}>

    {{ $slot }}

</button>
