{{-- Estilos compartidos de las cuadrículas de la planilla de turnos. --}}
<style>
    .planilla-semana {
        font-size: .85rem;
    }

    .planilla-semana th,
    .planilla-semana td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .planilla-semana .col-empleado {
        text-align: left;
        min-width: 110px;
        font-weight: 600;
    }

    .planilla-semana .dia-festivo {
        background-color: #f8d7da !important;
    }

    .planilla-semana .dia-fuera {
        background-color: #e9ecef !important;
        color: #adb5bd;
    }

    .planilla-semana .celda-turno {
        min-width: 105px;
        font-size: .8rem;
        padding: .2rem 1.6rem .2rem .4rem;
    }

    .planilla-semana .exceso {
        background-color: #f8d7da !important;
        color: #842029;
        font-weight: 700;
    }

    @media print {
        .planilla-semana {
            font-size: .7rem;
        }

        .planilla-semana .dia-festivo,
        .planilla-semana .dia-fuera,
        .planilla-semana .exceso,
        .planilla-semana td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
