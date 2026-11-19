{{-- Estilos compartidos de nómina: indicadores, insignias de estado, tablas de valores y pestañas. --}}
<style>
    .nm-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .6rem; margin-bottom: 1rem; }
    .nm-kpi { display: flex; align-items: center; gap: .7rem; padding: .75rem .9rem; background: #fff; border: 1px solid var(--bs-border-color);
        border-top: 4px solid var(--nm-color, #0d6efd); border-radius: .7rem; }
    .nm-kpi .nm-kpi-icono { width: 2.4rem; height: 2.4rem; border-radius: 50%; display: grid; place-items: center; flex-shrink: 0;
        background: var(--nm-suave, #e7f1ff); color: var(--nm-color, #0d6efd); font-size: 1.15rem; }
    .nm-kpi .nm-kpi-valor { font-size: 1.15rem; font-weight: 800; line-height: 1.1; white-space: nowrap; }
    .nm-kpi .nm-kpi-titulo { font-size: .74rem; color: #6c757d; text-transform: uppercase; letter-spacing: .02em; }
    .nm-azul { --nm-color: #0d6efd; --nm-suave: #e7f1ff; }
    .nm-verde { --nm-color: #198754; --nm-suave: #e8f5ee; }
    .nm-rojo { --nm-color: #dc3545; --nm-suave: #fdecee; }
    .nm-amarillo { --nm-color: #e0a800; --nm-suave: #fff8e1; }
    .nm-morado { --nm-color: #6f42c1; --nm-suave: #f1ebfb; }

    .nm-estado { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 700; }
    .nm-estado::before { content: ''; width: .55rem; height: .55rem; border-radius: 50%; background: currentColor; }
    .nm-estado.borrador { background: #fff8e1; color: #9a7400; border: 1px solid #e0a800; }
    .nm-estado.aprobada { background: #e8f5ee; color: #146c43; border: 1px solid #198754; }

    .nm-tabs .nav-link { font-weight: 600; color: #495057; }
    .nm-tabs .nav-link.active { color: #0d6efd; }

    .nm-tabla { font-size: .84rem; }
    .nm-tabla td.num, .nm-tabla th.num { text-align: right; white-space: nowrap; }
    .nm-tabla .fila-subtotal td { background: #f1f3f5; font-weight: 700; }
    .nm-tabla .fila-total td { background: #fff2cc; font-weight: 800; }
    .nm-tabla .fila-seccion td { background: #1f4e78; color: #fff; font-weight: 700; font-size: .78rem; letter-spacing: .03em; }
    .nm-tabla .col-fija { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 13rem; }
    .nm-tabla thead .col-fija { background: var(--bs-table-bg, #cfe2ff); z-index: 3; }
    .nm-tabla .fila-subtotal .col-fija { background: #f1f3f5; }
    .nm-tabla .fila-total .col-fija { background: #fff2cc; }

    .nm-novedades input.form-control, .nm-novedades select.form-select { min-width: 4.6rem; font-size: .82rem; padding: .2rem .35rem; text-align: right; }
    .nm-novedades input.dinero { min-width: 7rem; }
    .nm-novedades select.form-select { text-align: left; min-width: 8.5rem; }
    .nm-novedades .empleado small { display: block; color: #6c757d; font-size: .72rem; }

    .nm-pago { border: 1px solid var(--bs-border-color); border-radius: .7rem; padding: .8rem 1rem; background: #fff; height: 100%; }
    .nm-pago .nm-pago-valor { font-size: 1.25rem; font-weight: 800; }

    @media print {
        .nm-tabs, .nm-acciones { display: none !important; }
        .tab-content > .tab-pane { display: block !important; opacity: 1 !important; }
        .nm-tabla { font-size: 9px; }
    }
</style>
