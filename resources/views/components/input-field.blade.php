<div {{ $attributes->only('class') ? 'class=' . $attributes->only('class') : '' }}>
    @if (isset($label))
        <div class="field-label">{{ $label }}</div>
    @endif
    {{ $slot }}
</div>
