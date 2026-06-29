@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <style>
        .dashboard-line-chart-wrap {
            height: 210px;
        }

        .dashboard-donut-chart-wrap {
            width: 100%;
            max-width: 320px;
            height: 210px;
            margin: 0 auto;
        }
    </style>

    <div class="pastel-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="mb-0">Resumen rápido de turnos, clientes, combustibles y lubricantes.</p>
            </div>
            <div>
                <span class="badge bg-secondary text-white">Estación de Servicio</span>
            </div>
        </div>
    </div>

    @php $user = auth()->user(); @endphp

    <div class="row g-4 mb-4">
        @if ($user?->canAccessTurnos())
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 pastel-card shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">Turnos</h5>
                            <p class="card-text fs-1 fw-bold mb-1">0</p>
                            <p class="text-muted">Ver y crear turnos rápidamente.</p>
                        </div>
                        <a href="{{ route('turnos.create') }}" class="btn btn-primary pastel-button mt-3 w-100">Ir a
                            Turnos</a>
                    </div>
                </div>
            </div>
        @endif

        @if ($user?->isAdministrador())
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 pastel-card shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">Clientes</h5>
                            <p class="card-text fs-1 fw-bold mb-1">0</p>
                            <p class="text-muted">Gestiona los clientes de la estación.</p>
                        </div>
                        <a href="{{ route('customers.index') }}" class="btn btn-primary pastel-button mt-3 w-100">Ir a
                            Clientes</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 pastel-card shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">Combustibles</h5>
                            <p class="card-text fs-1 fw-bold mb-1">0</p>
                            <p class="text-muted">Consulta y actualiza precios de combustible.</p>
                        </div>
                        <a href="{{ route('fuel-prices.index') }}" class="btn btn-primary pastel-button mt-3 w-100">Ir a
                            Combustibles</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 pastel-card shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">Lubricantes</h5>
                            <p class="card-text fs-1 fw-bold mb-1">0</p>
                            <p class="text-muted">Revisa el inventario de lubricantes.</p>
                        </div>
                        <a href="{{ route('lubricants.index') }}" class="btn btn-primary pastel-button mt-3 w-100">Ir a
                            Lubricantes</a>
                    </div>
                </div>
            </div>
        @endif

        @if ($user?->canAccessCartera())
            <div class="col-lg-3 col-sm-6">
                <div class="card h-100 border-0 pastel-card shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">Cartera</h5>
                            <p class="card-text fs-1 fw-bold mb-1">0</p>
                            <p class="text-muted">Gestiona la cartera de la estación.</p>
                        </div>
                        <a href="{{ route('cartera.index') }}" class="btn btn-primary pastel-button mt-3 w-100">Ir a
                            Cartera</a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 pastel-card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Turnos por semana</h5>
                </div>
                <div class="card-body">
                    <div class="dashboard-line-chart-wrap">
                        <canvas id="turnosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 pastel-card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Ventas por combustible</h5>
                </div>
                <div class="card-body">
                    <div class="dashboard-donut-chart-wrap">
                        <canvas id="combustibleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const turnosCtx = document.getElementById('turnosChart');
            if (turnosCtx) {
                new Chart(turnosCtx, {
                    type: 'line',
                    data: {
                        labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                        datasets: [{
                            label: 'Turnos activos',
                            data: [12, 18, 14, 20, 16, 22, 18],
                            borderColor: '#a8d5e2',
                            backgroundColor: 'rgba(168, 213, 226, 0.28)',
                            pointBackgroundColor: '#f7c8de',
                            pointBorderColor: '#a8d5e2',
                            pointHoverBackgroundColor: '#f9e8c7',
                            pointHoverBorderColor: '#a8d5e2',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#3d3850',
                                bodyColor: '#3d3850',
                                borderColor: 'rgba(167, 139, 184, 0.25)',
                                borderWidth: 1
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(167, 139, 184, 0.12)'
                                },
                                ticks: {
                                    color: '#746d84'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(167, 139, 184, 0.12)'
                                },
                                ticks: {
                                    color: '#746d84'
                                }
                            }
                        }
                    }
                });
            }

            const combustibleCtx = document.getElementById('combustibleChart');
            if (combustibleCtx) {
                new Chart(combustibleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Corriente', 'ACPM', 'Diesel'],
                        datasets: [{
                            data: [45, 35, 20],
                            backgroundColor: ['#a8d5e2', '#f7c8de', '#f9e8c7'],
                            borderColor: ['#c3e5ef', '#f9d2e7', '#fce9cf'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#746d84'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#3d3850',
                                bodyColor: '#3d3850',
                                borderColor: 'rgba(167, 139, 184, 0.25)',
                                borderWidth: 1
                            }
                        }
                    }
                });
            }
        });
    </script>

@endsection
