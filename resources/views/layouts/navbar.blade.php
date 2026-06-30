<nav class="navbar navbar-expand-lg navbar-light navbar-pastel">

    <div class="container-fluid">

        <a class="navbar-brand" href="{{ route('dashboard') }}">
            ByH Agrocomercial SAS
        </a>

        <div class="d-flex align-items-center w-100">
            <ul class="navbar-nav me-auto d-flex flex-row">
                @auth
                    @php $user = auth()->user(); @endphp

                    <li class="nav-item me-3">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>

                    @if ($user->canAccessTurnos())
                        <li class="nav-item me-3">
                            <a class="nav-link text-white" href="{{ route('turnos.create') }}">Turnos</a>
                        </li>
                    @endif

                    @if ($user->canAccessCartera())
                        <li class="nav-item me-3">
                            <a class="nav-link text-white" href="{{ route('cartera.index') }}">Cartera</a>
                        </li>
                    @endif

                    @if ($user->isAdministrador())
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">Usuarios</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                                href="{{ route('customers.index') }}">Clientes</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('fuel-prices.*') ? 'active' : '' }}"
                                href="{{ route('fuel-prices.index') }}">Combustibles</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('lubricants.*') ? 'active' : '' }}"
                                href="{{ route('lubricants.index') }}">Lubricantes</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('lubricants.*') ? 'active' : '' }}"
                                href="{{ route('lubricants.index') }}">Compras</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="nav-link text-white {{ request()->routeIs('lubricants.*') ? 'active' : '' }}"
                                href="{{ route('lubricants.index') }}">Anticipo</a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                @auth
                    <li class="nav-item me-2 text-white d-flex align-items-center">
                        {{ auth()->user()->name ?? auth()->user()->email }}
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">Cerrar sesión</button>
                        </form>
                    </li>
                @endauth
            </ul>
        </div>

    </div>

</nav>
