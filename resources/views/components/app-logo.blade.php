@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="ByH Agrocomercial SAS" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/logo-dashboard-32.png') }}" alt="Logo ByH Agrocomercial SAS"
                class="h-6 w-6 rounded-sm object-contain sm:h-7 sm:w-7" loading="eager" decoding="async" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="ByH Agrocomercial SAS" {{ $attributes }}>
        <x-slot name="logo"
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/logo-dashboard-32.png') }}" alt="Logo ByH Agrocomercial SAS"
                class="h-6 w-6 rounded-sm object-contain sm:h-7 sm:w-7" loading="eager" decoding="async" />
        </x-slot>
    </flux:brand>
@endif
