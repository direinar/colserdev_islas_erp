{{--
    Menú responsive: en computador (lg y más) es la barra horizontal; en
    celulares y tablets se abre con el botón ☰ como panel lateral (offcanvas)
    con los submenús desplegados hacia abajo, sin salirse de la pantalla.
--}}
<nav class="navbar navbar-expand-lg navbar-light navbar-pastel shadow-sm sticky-top">

    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center fw-bold" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo-dashboard-32.png') }}" alt="Logo" class="brand-logo me-2">
            <span class="brand-text">ByH</span>
        </a>

        @auth
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu-principal"
                aria-controls="menu-principal" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
        @endauth

        <div class="offcanvas offcanvas-end offcanvas-lg navbar-offcanvas" tabindex="-1" id="menu-principal"
            aria-labelledby="menu-principal-titulo">

            <div class="offcanvas-header border-bottom">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('images/logo-dashboard-32.png') }}" alt="Logo" class="brand-logo me-2">
                    <div>
                        <h5 class="offcanvas-title fw-bold mb-0" id="menu-principal-titulo">ByH</h5>
                        @auth
                            <small class="text-muted">{{ auth()->user()->name }}</small>
                        @endauth
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menu-principal"
                    aria-label="Cerrar menú"></button>
            </div>

        <div class="offcanvas-body d-flex flex-column flex-lg-row align-items-lg-center w-100">

            {{-- ================= MENÚ ================= --}}
            <ul class="navbar-nav me-lg-auto align-items-lg-center gap-lg-1">

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
                                @if ($user->isAdministrador())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('turnos.pendientes') }}">
                                            Planillas pendientes de revisión
                                        </a>
                                    </li>
                                @endif
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
                                            Cartera - Estado de cuenta
                                        </a>
                                    </li>
                                @endif

                                @if ($user->isAdministrador())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('cartera-saldos-iniciales.index') }}">
                                            Saldos iniciales de cartera
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
                            <a class="nav-link dropdown-toggle {{ request()->is('fuel-prices*') || request()->is('lubricants*') || request()->is('proveedores*') || request()->is('bancos*') || request()->is('inventarios/gasolina*') || request()->is('inventarios/acpm*') || request()->is('inventarios/lubricantes*') || request()->is('inventarios/aditivo-motos*') || request()->is('inventarios/urea-automotriz*') ? 'active' : '' }}"
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
                                    <li>
                                        <a class="dropdown-item" href="{{ route('bancos.index') }}">
                                            Bancos
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
            <ul class="navbar-nav navbar-usuario ms-lg-auto align-items-lg-center gap-2 mt-3 mt-lg-0 pt-3 pt-lg-0">

                @auth
                    {{-- Se muestra solo cuando el navegador permite instalar la PWA (resources/js/pwa.js) --}}
                    <li class="nav-item d-none" data-pwa-install>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-pwa-install-button
                            title="Instalar el ERP como aplicación en este equipo">
                            <i class="bi bi-download"></i> Instalar aplicación
                        </button>
                    </li>

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                Cerrar sesión
                            </button>
                        </form>
                    </li>

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
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person me-2"></i>Mi perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('security.edit') }}">
                                    <i class="bi bi-shield-lock me-2"></i>Seguridad
                                </a>
                            </li>
                        </ul>
                    </li>
                @endauth

            </ul>

        </div>

        </div>

    </div>

</nav>
