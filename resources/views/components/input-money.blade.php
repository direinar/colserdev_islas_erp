<div class="{{ $attributes->get('class') }}">
    @if (isset($label))
        <div class="field-label">{{ $label }}</div>
    @endif
    <div class="money-field">
        {{ $slot }}
    </div>
</div>
