<style>
    .navbar-pastel {
        background-color: #6c757d;
        /* Cambia este color según tus preferencias */
    }

    .navbar-pastel .nav-link {
        color: #ffffff;
        /* Color del texto de los enlaces */
    }

    .navbar-pastel .nav-link:hover {
        color: #f8f9fa;
        /* Color del texto al pasar el mouse */
    }

    .navbar-pastel .dropdown-menu {
        background-color: #6c757d;
        /* Color de fondo del menú desplegable */
    }

    .navbar-pastel .dropdown-item {
        color: #ffffff;
        /* Color del texto de los elementos del menú desplegable */
    }

    .navbar-pastel .dropdown-item:hover {
        background-color: #5a6268;
        /* Color de fondo al pasar el mouse sobre un elemento del menú desplegable */
        color: #f8f9fa;
        /* Color del texto al pasar el mouse sobre un elemento del menú desplegable */
    }
</style>


<nav class="navbar navbar-expand-lg navbar-light navbar-pastel shadow-sm">

    <div class="container-fluid">

        <a class="navbar-brand fw-bold text-white" href="{{ route('dashboard') }}">
            ByH Agrocomercial SAS
        </a>

        <div class="d-flex align-items-center w-100">

            {{-- ================= MENÚ ================= --}}
            <ul class="navbar-nav me-auto d-flex flex-row align-items-center">

                @auth

                    @php($user = auth()->user())

                    {{-- Dashboard --}}
                    <li class="nav-item me-2">
                        <a class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            Dashboard
                        </a>
                    </li>

                    {{-- OPERACIÓN --}}
                    @if ($user->canAccessTurnos())
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

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
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

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
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

                                Inventario

                            </a>

                            <ul class="dropdown-menu">

                                @if ($user->isAdministrador())
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
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

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


                    {{-- ADMINISTRACION --}}
                    @if ($user->isAdministrador())
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

                                Administracion

                            </a>

                            <ul class="dropdown-menu">

                                <li>
                                    <a class="dropdown-item" href="{{ route('users.index') }}">
                                        Usuarios
                                    </a>
                                </li>

                            </ul>

                        </li>
                    @endif


                    {{-- INFORMES --}}
                    @if ($user->canAccessAnticipoBimestral())
                        <li class="nav-item dropdown me-2">

                            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

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

            {{-- Usuario --}}
            <ul class="navbar-nav ms-auto">

                @auth

                    <li class="nav-item dropdown">

                        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">

                            {{ auth()->user()->name }}

                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <span class="dropdown-item-text">
                                    {{ auth()->user()->email }}
                                </span>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>

                                <form method="POST" action="{{ route('logout') }}">
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
