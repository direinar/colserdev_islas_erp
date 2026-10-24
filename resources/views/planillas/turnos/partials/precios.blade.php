{{-- Precios del turno. La lectura electrónica de los surtidores ya no va en la planilla:
     se registra por fecha en Informes → Lectura electrónica. --}}
<div class="card text-center mb-3">
    <div class="card-header py-1 fw-bold" style="background-color: #b5fdd2;">PRECIOS ACTUALES</div>
    <div class="card-body py-2">
        <div class="row g-2">
            <div class="col-6">
                <div class="fw-bold small">CORRIENTE</div>
                <div>{{ number_format($precioCorriente, 0, ',', '.') }}</div>
            </div>
            <div class="col-6">
                <div class="fw-bold small">ACPM</div>
                <div>{{ number_format($precioAcpm, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>
