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
                    @if ($user->canAccessDashboard())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                    @endif

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
                                        Planilla de turnos
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
                            <a class="nav-link dropdown-toggle {{ request()->is('fuel-prices*') || request()->is('lubricants*') || request()->is('proveedores*') || request()->is('bancos*') || request()->is('inventarios/gasolina*') || request()->is('inventarios/acpm*') || request()->is('inventarios/lubricantes*') || request()->is('inventarios/saldos-iniciales*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Inventario
                            </a>
                            <ul class="dropdown-menu">
                                @if ($user->isAdministrador())
                                    <li><h6 class="dropdown-header">Inventarios (kardex)</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-gasolina.create') }}">
                                            Gasolina
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-acpm.create') }}">
                                            ACPM
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-lubricantes.create') }}">
                                            Canastilla por referencia
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-lubricantes.control') }}">
                                            Canastilla – control
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventarios-saldos-iniciales.index') }}">
                                            Saldos iniciales de inventario
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Catálogos</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('fuel-prices.index') }}">
                                            Precios de combustible
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('lubricants.index') }}">
                                            Precios de canastilla
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('bancos.index') }}">
                                            Bancos recaudadores
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
                    @if ($user->canAccessCompras() || $user->canAccessComprasLubricantes() || $user->canAccessComprobanteContableCompras() || $user->canAccessAnticipoBimestral())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('compras*') || request()->is('comprobante*') || request()->is('anticipo-bimestral*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Compras
                            </a>
                            <ul class="dropdown-menu">
                                @if ($user->canAccessCompras())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('compras.create') }}">
                                            Compras de combustible
                                        </a>
                                    </li>
                                @endif

                                @if ($user->canAccessComprasLubricantes())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('compras-lubricantes.create') }}">
                                            Compras de canastilla
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif


                    {{-- INFORMES: solo consulta; los registros manuales están en Compras. --}}
                    @if ($user->canAccessAnticipoBimestral() || $user->canAccessControlTanques())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('control-tanques*') || request()->is('informes*') ? 'active' : '' }}"
                                href="#" data-bs-toggle="dropdown">
                                Informes
                            </a>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Operación</h6></li>
                                @if ($user->canAccessControlTanques())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('control-tanques.index') }}">
                                            Control de tanques
                                        </a>
                                    </li>
                                @endif
                                @if ($user->isAdministrador())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.control-caja.index') }}">
                                            Control de caja
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.margen-mayorista.index') }}">
                                            Margen mayorista
                                        </a>
                                    </li>
                                @endif

                                @if ($user->isAdministrador())
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Cartera y saldos</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.recaudos-cartera.index') }}">
                                            Informe de recaudos de cartera
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.saldos.index') }}">
                                            Saldos (cartera, canastilla y precios)
                                        </a>
                                    </li>
                                @endif

                                @if ($user->isAdministrador())
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Contables</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.comprobante-contable-compras.index') }}">
                                            Comprobante contable de compras
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.comprobante-ventas.index') }}">
                                            Comprobante contable de ventas
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.comprobante-recaudos.index') }}">
                                            Comprobante contable de recaudos de cartera
                                        </a>
                                    </li>
                                @endif

                                @if ($user->isAdministrador())
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header">Fiscales y de gestión</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.anticipo-bimestral.index') }}">
                                            Anticipo bimestral
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('informes.cierre.index') }}">
                                            Informe de gestión
                                        </a>
                                    </li>
                                @endif
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
