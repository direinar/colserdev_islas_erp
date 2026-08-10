<x-layouts::auth :title="__('Log in')">
    <div>
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <label for="email">{{ __('Correo') }}</label>
            <flux:input id="email" name="email" :value="old('email')" type="email" required autofocus
                autocomplete="email" placeholder="Correo electrónico" />

            <div>
                <label for="password">{{ __('Contraseña') }}</label>
                <flux:input id="password" name="password" type="password" required autocomplete="current-password"
                    :placeholder="__('Contraseña')" viewable />

                @if (Route::has('password.request'))
                    <div class="text-end mt-2">
                        <flux:link class="forgot" :href="route('password.request')" wire:navigate>
                            {{ __('¿Olvidaste tu contraseña?') }}
                        </flux:link>
                    </div>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Recuérdame')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="btn-submit" data-test="login-button">
                {{ __('Iniciar sesión') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <div class="auth-footer">
                <span>{{ __('¿No tienes una cuenta?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Regístrate') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
