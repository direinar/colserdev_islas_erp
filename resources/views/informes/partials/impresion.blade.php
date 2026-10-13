{{--
    Estilos de impresión de los listados: oculta el menú y las acciones, y no recorta la tabla.
    Parámetro opcional: $horizontal (true para imprimir en horizontal).
--}}
<style>
    @page {
        size: {{ ($horizontal ?? false) ? 'landscape' : 'portrait' }};
        margin: 10mm;
    }

    @media print {
        nav,
        .navbar {
            display: none !important;
        }

        body > .container-fluid {
            padding: 0 !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        table {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table a {
            color: inherit;
            text-decoration: none;
        }
    }
</style>
