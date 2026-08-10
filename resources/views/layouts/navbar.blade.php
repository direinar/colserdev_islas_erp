{{-- ¡Asegúrate de borrar el bloque <style> que tenías aquí arriba! --}}

<nav class="navbar navbar-expand-lg navbar-light navbar-pastel shadow-sm">

    <div class="container-fluid">

        {{-- Se quitó text-white para que tome el color var(--ink) de tu custom.css --}}
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
            ByH Agrocomercial SAS
        </a>

        <div class="d-flex align-items-center w-100">

            {{-- ================= MENÚ ================= --}}
            <ul class="navbar-nav me-auto d-flex flex-row align-items-center gap-1">

                @auth

                    @php($user = auth()->user())

                    {{-- Dashboard --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            Dashboard
                        </a>
                    </li>

                    {{-- OPERACIÓN --}}
                    @if ($user->canAccessTurnos())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('turnos*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Operación
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('turnos.create') }}">
                                        Turnos
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif


                    {{-- COMERCIAL --}}
                    @if ($user->canAccessCartera() || $user->isAdministrador())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('cartera*') || request()->is('customers*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Comercial
                            </a>
                            <ul class="dropdown-menu">
                                @if ($user->canAccessCartera())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('cartera.index') }}">
                                            Cartera
                                        </a>
                                    </li>
                                @endif

                                @if ($user->isAdministrador())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('customers.index') }}">
                                            Clientes
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif


                    {{-- INVENTARIO --}}
                    @if ($user->isAdministrador() || $user->canAccessProveedores())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('fuel-prices*') || request()->is('lubricants*') || request()->is('proveedores*') || request()->is('inventarios/gasolina*') || request()->is('inventarios/acpm*') || request()->is('inventarios/lubricantes*') || request()->is('inventarios/aditivo-motos*') || request()->is('inventarios/urea-automotriz*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Inventario
                            </a>
                            <ul class="dropdown-menu">
                                @if ($user->isAdministrador())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-urea-automotriz.create') }}">
                                            Inventarios Urea Automotriz
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-aditivo-motos.create') }}">
                                            Inventarios Aditivo Motos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-lubricantes.create') }}">
                                            Inventarios Lubricantes
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-acpm.create') }}">
                                            Inventarios ACPM
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-gasolina.create') }}">
                                            Inventarios Gasolina
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('fuel-prices.index') }}">
                                            Combustibles
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('lubricants.index') }}">
                                            Lubricantes
                                        </a>
                                    </li>
                                @endif

                                @if ($user->canAccessProveedores())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('proveedores.index') }}">
                                            Proveedores
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif


                    {{-- COMPRAS --}}
                    @if ($user->canAccessCompras() || $user->canAccessComprasLubricantes() || $user->canAccessComprobanteContableCompras())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('compras*') || request()->is('comprobante*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Compras
                            </a>
                            <ul class="dropdown-menu">
                                @if ($user->canAccessCompras())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('compras.create') }}">
                                            Compras Combustible
                                        </a>
                                    </li>
                                @endif

                                @if ($user->canAccessComprasLubricantes())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('compras-lubricantes.create') }}">
                                            Compras Lubricantes
                                        </a>
                                    </li>
                                @endif

                                @if ($user->canAccessComprobanteContableCompras())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('comprobante-contable-compras.create') }}">
                                            Comprobante Contable
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif


                    {{-- INFORMES --}}
                    @if ($user->canAccessAnticipoBimestral())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('anticipo*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Informes
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('anticipo-bimestral.create') }}">
                                        Anticipo Bimestral
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                @endauth

            </ul>

            {{-- ================= Usuario y Logout ================= --}}
            <ul class="navbar-nav ms-auto">

                @auth
                    <li class="nav-item dropdown">
                        {{-- Se quitó text-white --}}
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text text-muted">
                                    {{ auth()->user()->email }}
                                </span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button class="dropdown-item text-danger">
                                        Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth

            </ul>

        </div>

    </div>

</nav>
