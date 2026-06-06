<div wire:loading.class="opacity-75">

    {{-- ══════════════════════════════════════════════════════════════════
         ENCABEZADO
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-dot"></div>
            <div class="card-title">Planilla de Turnos</div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr auto auto auto 1fr;gap:16px;align-items:end;">
                <div class="header-field">
                    <div class="field-label">Nombre del Vendedor</div>
                    <input type="text" wire:model.defer="nombre_vendedor" placeholder="Nombre completo">
                </div>
                <div class="header-field">
                    <div class="field-label">Fecha</div>
                    <input type="date" wire:model.defer="fecha">
                </div>
                <div class="header-field">
                    <div class="field-label">Turno No.</div>
                    <input type="number" wire:model.defer="numero_turno" min="1" style="width:80px;text-align:center;">
                </div>
                <div class="header-field">
                    <div class="field-label">Revisado por</div>
                    <input type="text" wire:model.defer="revisado_por" placeholder="Firma / nombre">
                </div>
                <div></div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FILA 1: VENTAS SURTIDORES  |  CONSIGNACIONES + CARTERA
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="cols-layout">

        {{-- VENTAS SEGÚN CIERRES DE IAPROPIADA --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-dot"></div>
                <div class="card-title">Ventas según Cierres de IAPROPIADA</div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="t">
                    <thead>
                        <tr>
                            <th>Surtidor</th>
                            <th>Tipo</th>
                            <th style="text-align:right;">Galones</th>
                            <th style="text-align:right;">Valor ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ventas_surtidor as $i => $v)
                        <tr>
                            <td class="label-cell">{{ $v['surtidor'] }}</td>
                            <td style="width:52px;">
                                <span class="tipo-badge {{ $v['tipo'] === 'CTE' ? 'tipo-cte' : 'tipo-acpm' }}">
                                    {{ $v['tipo'] }}
                                </span>
                            </td>
                            <td style="width:90px;">
                                <input type="text"
                                    wire:model.defer="ventas_surtidor.{{ $i }}.galones"
                                    placeholder="0,000"
                                    style="text-align:right;">
                            </td>
                            <td style="width:110px;">
                                <input type="text"
                                    wire:model.defer="ventas_surtidor.{{ $i }}.valor"
                                    placeholder="0"
                                    style="text-align:right;">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">CORRIENTE</td>
                            <td style="text-align:right;">{{ number_format($totalGalonesCte, 3, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($totalValorCte, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">ACPM</td>
                            <td style="text-align:right;">{{ number_format($totalGalonesAcpm, 3, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($totalValorAcpm, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row" style="border-top:2px solid var(--amber);">
                            <td class="label-cell" colspan="2" style="color:var(--amber2);font-weight:800;">TOTAL VENTA</td>
                            <td style="text-align:right;">{{ number_format($totalGalonesCte + $totalGalonesAcpm, 3, ',', '.') }}</td>
                            <td style="text-align:right;font-size:14px;">{{ number_format($totalVentaIapropiada, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- MEDIOS DE PAGO: CONSIGNACIONES + CARTERA --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- Consignaciones --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">Consignaciones</div>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>No. Consig.</th>
                                <th style="text-align:right;">Valor ($)</th>
                                <th style="text-align:right;">Descuento ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consignaciones as $i => $c)
                            <tr>
                                <td><input type="text" wire:model.defer="consignaciones.{{ $i }}.numero" placeholder="—"></td>
                                <td style="width:110px;"><input type="text" wire:model.defer="consignaciones.{{ $i }}.valor" placeholder="0" style="text-align:right;"></td>
                                <td style="width:110px;"><input type="text" wire:model.defer="consignaciones.{{ $i }}.descuento" placeholder="0" style="text-align:right;"></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell">TOTALES</td>
                                <td style="text-align:right;">{{ number_format($totalConsignaciones, 0, ',', '.') }}</td>
                                <td style="text-align:right;">{{ number_format($totalDescuentos, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Cartera / Crédito Directo --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">Cartera – Crédito Directo</div>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>No. Factura</th>
                                <th>Cliente</th>
                                <th style="text-align:right;">Valor ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cartera as $i => $c)
                            <tr>
                                <td style="width:100px;"><input type="text" wire:model.defer="cartera.{{ $i }}.numero_factura" placeholder="—"></td>
                                <td><input type="text" wire:model.defer="cartera.{{ $i }}.cliente" placeholder="Nombre cliente"></td>
                                <td style="width:110px;"><input type="text" wire:model.defer="cartera.{{ $i }}.valor" placeholder="0" style="text-align:right;"></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell" colspan="2">TOTAL CARTERA</td>
                                <td style="text-align:right;">{{ number_format($totalCartera, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FILA 2: LECTURAS ELECTRÓNICAS  |  TC / QR / NEQUI
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="cols-layout">

        {{-- LECTURA ELECTRÓNICA DE SURTIDORES --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-dot"></div>
                <div class="card-title">Lectura Electrónica de Surtidores</div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="t">
                    <thead>
                        <tr>
                            <th>Manguera</th>
                            <th>Tipo</th>
                            <th style="text-align:right;">Inicial</th>
                            <th style="text-align:right;">Final</th>
                            <th style="text-align:right;">Gls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lecturas as $i => $l)
                        @php
                            $gls = max(0, (float)str_replace(['.', ','],['','.'], $l['final'] ?? '0') - (float)str_replace(['.', ','],['','.'], $l['inicial'] ?? '0'));
                        @endphp
                        <tr>
                            <td class="label-cell">"{{ $l['manguera'] }}"</td>
                            <td style="width:52px;">
                                <span class="tipo-badge {{ $l['tipo'] === 'CTE' ? 'tipo-cte' : 'tipo-acpm' }}">
                                    {{ $l['tipo'] }}
                                </span>
                            </td>
                            <td style="width:110px;">
                                <input type="text" wire:model.defer="lecturas.{{ $i }}.inicial" placeholder="0,000" style="text-align:right;">
                            </td>
                            <td style="width:110px;">
                                <input type="text" wire:model.defer="lecturas.{{ $i }}.final" placeholder="0,000" style="text-align:right;">
                            </td>
                            <td style="width:80px;text-align:right;color:var(--text-dim);padding:4px 8px;">
                                {{ $gls > 0 ? number_format($gls, 3, ',', '.') : '0,000' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">CORRIENTE – Galones</td>
                            <td colspan="2"></td>
                            <td style="text-align:right;">{{ number_format($lecturaGalonesCte, 3, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">ACPM – Galones</td>
                            <td colspan="2"></td>
                            <td style="text-align:right;">{{ number_format($lecturaGalonesAcpm, 3, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">Venta CTE ($)</td>
                            <td colspan="2" style="text-align:right;font-size:10px;color:var(--text-dim);">
                                Precio: ${{ number_format($precioCte, 0, ',', '.') }}/gls
                            </td>
                            <td style="text-align:right;">{{ number_format($lecturaValorCte, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">Venta ACPM ($)</td>
                            <td colspan="2" style="text-align:right;font-size:10px;color:var(--text-dim);">
                                Precio: ${{ number_format($precioAcpm, 0, ',', '.') }}/gls
                            </td>
                            <td style="text-align:right;">{{ number_format($lecturaValorAcpm, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row" style="border-top:2px solid var(--amber);">
                            <td class="label-cell" colspan="4" style="color:var(--amber2);font-weight:800;">TOTAL LECTURA</td>
                            <td style="text-align:right;font-size:14px;">{{ number_format($totalVentaLectura, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- MEDIOS PAGO ELECTRÓNICOS + RECAUDOS --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- TC / QR / Nequi / Daviplata --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">TC, QR, Nequi y Daviplata</div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Total TC Datáfono 1</div>
                            <input type="number" wire:model.defer="tc_datafono_1" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Total TC Datáfono 2</div>
                            <input type="number" wire:model.defer="tc_datafono_2" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Total TC Datáfono 3</div>
                            <input type="number" wire:model.defer="tc_datafono_3" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Puntos Redimidos</div>
                            <input type="number" wire:model.defer="puntos_redimidos" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Transferencias Bancolombia</div>
                            <input type="number" wire:model.defer="transferencias_bancolombia" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Gasolina EDS</div>
                            <input type="number" wire:model.defer="gasolina_eds" placeholder="0" style="text-align:right;">
                        </div>
                    </div>
                    <hr class="divider">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="field-label">Total Medios Electrónicos</span>
                        <span class="inline-total">$ {{ number_format($totalMediosPagoElectronicos, 0, ',', '.') }}</span>
                    </div>
                    <div style="margin-top:6px;display:flex;justify-content:space-between;align-items:center;">
                        <span class="field-label">Total TC (Datáfonos)</span>
                        <span class="inline-total">$ {{ number_format($totalTc, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Recaudos, Anticipos y Prepagos por Islas --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">Recaudos, Anticipos y Prepagos por Islas</div>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th style="text-align:right;">Valor ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recaudos_anticipos as $i => $r)
                            <tr>
                                <td><input type="text" wire:model.defer="recaudos_anticipos.{{ $i }}.cliente" placeholder="Nombre cliente"></td>
                                <td style="width:120px;"><input type="text" wire:model.defer="recaudos_anticipos.{{ $i }}.valor" placeholder="0" style="text-align:right;"></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell">TOTAL RECAUDOS</td>
                                <td style="text-align:right;">{{ number_format($totalRecaudos, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FILA 3: UREA Y LUBRICANTES  |  VARIOS + RECAUDOS ADMIN
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="cols-layout">

        {{-- VENTAS DE UREA Y LUBRICANTES --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-dot"></div>
                <div class="card-title">Ventas de Urea y Lubricantes – Contado y Crédito</div>
            </div>
            <div class="card-body" style="padding:0;">
                <table class="t">
                    <thead>
                        <tr>
                            <th style="width:60px;">Cant.</th>
                            <th>Producto</th>
                            <th style="text-align:right;">Val. sin IVA</th>
                            <th style="text-align:right;">IVA</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($urea_lubricantes as $i => $u)
                        @php
                            $tot = (float)str_replace(['.', ','],['','.'], $u['valor_sin_iva'] ?? '0')
                                 + (float)str_replace(['.', ','],['','.'], $u['iva'] ?? '0');
                        @endphp
                        <tr>
                            <td><input type="text" wire:model.defer="urea_lubricantes.{{ $i }}.cantidad" placeholder="0" style="text-align:center;"></td>
                            <td><input type="text" wire:model.defer="urea_lubricantes.{{ $i }}.producto" placeholder="Descripción"></td>
                            <td style="width:100px;"><input type="text" wire:model.defer="urea_lubricantes.{{ $i }}.valor_sin_iva" placeholder="0" style="text-align:right;"></td>
                            <td style="width:80px;"><input type="text" wire:model.defer="urea_lubricantes.{{ $i }}.iva" placeholder="0" style="text-align:right;"></td>
                            <td style="width:100px;text-align:right;color:var(--text-dim);padding:4px 8px;">
                                {{ $tot > 0 ? number_format($tot, 0, ',', '.') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="label-cell" colspan="2">TOTALES</td>
                            <td style="text-align:right;">{{ number_format($totalUreaSinIva, 0, ',', '.') }}</td>
                            <td style="text-align:right;">{{ number_format($totalUreaIva, 0, ',', '.') }}</td>
                            <td style="text-align:right;font-size:14px;">{{ number_format($totalUreaTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- VARIOS + RECAUDOS ADMINISTRACIÓN --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- Varios --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">Varios</div>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th style="text-align:right;">Valor ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($varios as $i => $v)
                            <tr>
                                <td><input type="text" wire:model.defer="varios.{{ $i }}.concepto" placeholder="Concepto"></td>
                                <td style="width:120px;"><input type="text" wire:model.defer="varios.{{ $i }}.valor" placeholder="0" style="text-align:right;"></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell">TOTAL VARIOS</td>
                                <td style="text-align:right;">{{ number_format($totalVarios, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Recaudos por Administración --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-header-dot"></div>
                    <div class="card-title">Recaudos por Administración</div>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="t">
                        <thead>
                            <tr>
                                <th>Banco / Caja</th>
                                <th>Cliente</th>
                                <th style="text-align:right;">Valor ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recaudos_admin as $i => $r)
                            <tr>
                                <td style="width:120px;"><input type="text" wire:model.defer="recaudos_admin.{{ $i }}.banco_caja" placeholder="Banco"></td>
                                <td><input type="text" wire:model.defer="recaudos_admin.{{ $i }}.cliente" placeholder="Cliente"></td>
                                <td style="width:120px;"><input type="text" wire:model.defer="recaudos_admin.{{ $i }}.valor" placeholder="0" style="text-align:right;"></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell" colspan="2">TOTAL RECAUDOS ADMIN</td>
                                <td style="text-align:right;">{{ number_format($totalRecaudosAdmin, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         RESUMEN FINAL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-dot"></div>
            <div class="card-title">Resumen del Turno</div>
        </div>
        <div class="card-body">

            <div class="cols-layout">
                {{-- Resumen vendido --}}
                <div>
                    <div class="field-label" style="margin-bottom:8px;">Resumen de lo Vendido</div>
                    <table class="t">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th style="text-align:right;">S/ IAPROPIADA</th>
                                <th style="text-align:right;">S/ Surtidores</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Combustible</td>
                                <td style="text-align:right;">{{ number_format($totalVentaIapropiada, 0, ',', '.') }}</td>
                                <td style="text-align:right;">{{ number_format($totalVentaLectura, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Urea y Lubricantes</td>
                                <td style="text-align:right;">{{ number_format($totalUreaTotal, 0, ',', '.') }}</td>
                                <td style="text-align:right;">{{ number_format($totalUreaTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell">TOTALES</td>
                                <td style="text-align:right;">{{ number_format($totalVentaIapropiada + $totalUreaTotal, 0, ',', '.') }}</td>
                                <td style="text-align:right;">{{ number_format($totalVentaLectura + $totalUreaTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <hr class="divider">

                    <div class="field-label" style="margin-bottom:8px;">Faltantes o Sobrantes</div>
                    <table class="t">
                        <tbody>
                            <tr>
                                <td class="label-cell">S/ Cierres IAPROPIADA</td>
                                <td style="text-align:right;padding:4px 8px;"
                                    class="{{ $sobranteFaltanteCierres >= 0 ? 'diff-positive' : 'diff-negative' }}">
                                    {{ number_format($sobranteFaltanteCierres, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label-cell">S/ Lectura Surtidores</td>
                                <td style="text-align:right;padding:4px 8px;"
                                    class="{{ $sobranteFaltanteLectura >= 0 ? 'diff-positive' : 'diff-negative' }}">
                                    {{ number_format($sobranteFaltanteLectura, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;">
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Traslado a Sobrante</div>
                            <input type="number" wire:model.defer="traslado_sobrante" placeholder="0" style="text-align:right;">
                        </div>
                        <div>
                            <div class="field-label" style="margin-bottom:4px;">Traslado a Faltante</div>
                            <input type="number" wire:model.defer="traslado_faltante" placeholder="0" style="text-align:right;">
                        </div>
                    </div>
                </div>

                {{-- Resumen recibido --}}
                <div>
                    <div class="field-label" style="margin-bottom:8px;">Resumen de lo Recibido</div>
                    <table class="t">
                        <tbody>
                            <tr>
                                <td class="label-cell">Consignaciones</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($totalConsignaciones, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">TC, QR, Nequi, Daviplata</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($totalTc, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Puntos Redimidos</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($puntos_redimidos, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Gasolina EDS</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($gasolina_eds, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Transferencias Bancolombia</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($transferencias_bancolombia, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Descuentos</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($totalDescuentos, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Cartera – Crédito Directo</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($totalCartera, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Varios</td>
                                <td style="text-align:right;padding:4px 8px;">{{ number_format($totalVarios, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td class="label-cell">Subtotal Ingresos</td>
                                <td style="text-align:right;">{{ number_format($subtotalIngresos, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row">
                                <td class="label-cell">Recaudos y Anticipos</td>
                                <td style="text-align:right;">{{ number_format($totalRecaudos, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="total-row" style="border-top:2px solid var(--amber);">
                                <td class="label-cell" style="color:var(--amber2);font-weight:800;font-size:14px;">TOTAL RECIBIDO</td>
                                <td style="text-align:right;font-size:18px;color:var(--amber2);">
                                    $ {{ number_format($totalRecibido, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- KPI summary row --}}
            <hr class="divider" style="margin-top:16px;">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Venta S/ IAPROPIADA</div>
                    <div class="summary-value">$ {{ number_format($totalVentaIapropiada + $totalUreaTotal, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Venta S/ Surtidores</div>
                    <div class="summary-value">$ {{ number_format($totalVentaLectura + $totalUreaTotal, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Recibido en el Turno</div>
                    <div class="summary-value" style="font-size:22px;">$ {{ number_format($totalRecibido, 0, ',', '.') }}</div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         ACCIONES
    ══════════════════════════════════════════════════════════════════ --}}
    <div style="display:flex;gap:12px;align-items:center;padding:12px 0;">
        <button class="btn btn-primary"
            wire:click="guardar"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-60"
            @if($guardado) disabled @endif>
            <span wire:loading.remove wire:target="guardar">💾 Guardar Turno</span>
            <span wire:loading wire:target="guardar">Guardando…</span>
        </button>

        @if($guardado)
        <button class="btn btn-ghost" wire:click="nuevoTurno">
            + Nuevo Turno
        </button>
        @endif

        @if($mensaje)
        <div class="alert {{ str_starts_with($mensaje, 'Error') ? 'alert-error' : 'alert-success' }}">
            {{ $mensaje }}
        </div>
        @endif

        <div wire:loading wire:target="guardar" style="color:var(--text-dim);font-size:11px;">
            ⟳ Procesando…
        </div>
    </div>

</div>
