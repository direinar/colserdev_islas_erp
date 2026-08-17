@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- Styles moved to resources/css/custom.css --}}

    <div class="pastel-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="mb-0">Resumen gráfico de turnos y ventas.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if (auth()->check() && auth()->user()->isAdministrador())
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                        Gestionar usuarios
                    </a>
                @endif
                <span class="badge bg-secondary text-white">Estación de Servicio</span>
            </div>
        </div>
    </div>

    <!-- Contenedor de las dos gráficas -->
    <div class="row g-4 mt-2">
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

    <!-- Script de Chart.js para dibujar los gráficos -->
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
