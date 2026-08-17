<div wire:loading.class="opacity-75">
    <?php
    // Ensure computed properties are available as local variables for the template
    $precioCte = $this->precioCte;
    $precioAcpm = $this->precioAcpm;
    
    $totalGalonesCte = $this->totalGalonesCte;
    $totalGalonesAcpm = $this->totalGalonesAcpm;
    $totalValorCte = $this->totalValorCte;
    $totalValorAcpm = $this->totalValorAcpm;
    $totalVentaIapropiada = $this->totalVentaIapropiada;
    
    $lecturaGalonesCte = $this->lecturaGalonesCte;
    $lecturaGalonesAcpm = $this->lecturaGalonesAcpm;
    $lecturaValorCte = $this->lecturaValorCte;
    $lecturaValorAcpm = $this->lecturaValorAcpm;
    $totalVentaLectura = $this->totalVentaLectura;
    
    $totalConsignaciones = $this->totalConsignaciones;
    $totalDescuentos = $this->totalDescuentos;
    $totalCartera = $this->totalCartera;
    $totalTc = $this->totalTc;
    $totalUreaSinIva = $this->totalUreaSinIva;
    $totalUreaIva = $this->totalUreaIva;
    $totalUreaTotal = $this->totalUreaTotal;
    $totalRecaudos = $this->totalRecaudos;
    $totalRecaudosAdministracion = $this->totalRecaudosAdministracion ?? 0;
    $totalMediosPagoElectronicos = $this->totalMediosPagoElectronicos ?? 0;
    $totalVarios = $this->totalVarios;
    $subtotalIngresos = $this->subtotalIngresos;
    $totalRecibido = $this->totalRecibido;
    $totalVendidoIapropiada = $this->totalVendidoIapropiada;
    $totalVendidoLectura = $this->totalVendidoLectura;
    $faltanteSobranteIapropiada = $this->faltanteSobranteIapropiada;
    $faltanteSobranteLectura = $this->faltanteSobranteLectura;
    ?>
    {{-- Styles moved to resources/css/custom.css --}}

    <div class="wrap">
        <div class="turno-shell">
            <section class="hero">
                <div class="hero-top">
                    <div>
                        <div class="hero-kicker">Planilla de turnos</div>
                        <div class="hero-title">Formulario único para el turno de gasolinera</div>
                        <div class="hero-subtitle">
                            La pantalla concentra ventas, lecturas, medios de pago, cartera, urea/lubricantes y el
                            resumen operativo.
                            Los campos calculados quedan bloqueados para evitar inconsistencias con la lógica descrita
                            en la documentación.
                        </div>
                    </div>
                    <div class="status-pill">{{ $guardado ? 'Guardado' : 'Borrador' }}</div>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-primary" wire:click="guardar" wire:loading.attr="disabled">
                        Guardar turno
                    </button>
                    <button type="button" class="btn btn-secondary" wire:click="nuevoTurno"
                        wire:loading.attr="disabled">
                        Nuevo turno
                    </button>
                    @if ($mensaje !== '')
                        <span class="feedback">{{ $mensaje }}</span>
                    @endif
                </div>
            </section>

            <x-card title="Encabezado del turno">
                <div class="form-grid">
                    <x-input-field class="field" :label="'Nombre del vendedor'">
                        <input type="text" wire:model.live="nombre_vendedor" placeholder="Nombre completo">
                    </x-input-field>

                    <x-input-field class="field" :label="'Fecha'">
                        <input type="date" wire:model.live="fecha">
                    </x-input-field>

                    <x-input-field class="field" :label="'Turno No.'">
                        <input type="number" min="1" wire:model.live="numero_turno">
                    </x-input-field>

                    <x-input-field class="field" :label="'Revisado por'">
                        <input type="text" wire:model.live="revisado_por" placeholder="Firma / nombre">
                    </x-input-field>
                </div>
            </x-card>

            <section class="card">
                <div class="card-header">
                    <div class="card-dot"></div>
                    <div class="card-title">Precios de combustible</div>
                </div>
                <div class="card-body">
                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 12px;">
                        <x-input-money class="field" :label="'Precio Corriente (CTE) / gl'">
                            <input type="text" inputmode="decimal" data-decimals="0" wire:model.live="precios.CTE"
                                class="tight-input" placeholder="0">
                        </x-input-money>

                        <x-input-money class="field" :label="'Precio ACPM / gl'">
                            <input type="text" inputmode="decimal" data-decimals="0" wire:model.live="precios.ACPM"
                                class="tight-input" placeholder="0">
                        </x-input-money>
                    </div>
                </div>
            </section>

            <div class="grid-two">
                <section class="card">
                    <div class="card-header">
                        <div class="card-dot"></div>
                        <div class="card-title">Ventas segun cierres de IAPROPIADA</div>
                    </div>
                    <div class="card-body">
                        <table class="planilla">
                            <thead>
                                <tr>
                                    <th>Surtidor</th>
                                    <th>Tipo</th>
                                    <th style="text-align:right;">Galones</th>
                                    <th style="text-align:right;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ventas_surtidor as $index => $venta)
                                    <tr wire:key="venta-{{ $index }}">
                                        <td class="label-cell">{{ $venta['surtidor'] }}</td>
                                        <td>
                                            <span
                                                class="type-badge {{ $venta['tipo'] === 'CTE' ? 'type-cte' : 'type-acpm' }}">{{ $venta['tipo'] }}</span>
                                        </td>
                                        <td style="width: 110px;">
                                            <input type="text" inputmode="decimal" class="row-input"
                                                wire:model.lazy="ventas_surtidor.{{ $index }}.galones"
                                                placeholder="0,000" style="text-align:right;">
                                        </td>
                                        <td class="money" style="width: 130px;">
                                            <strong>{{ number_format(((float) ($venta['galones'] ?? 0)) * ($venta['tipo'] === 'CTE' ? $precioCte : $precioAcpm), 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="tfoot-total">
                                    <td colspan="2">Corriente</td>
                                    <td style="text-align:right;">{{ number_format($totalGalonesCte, 3, ',', '.') }}
                                    </td>
                                    <td style="text-align:right;">{{ number_format($totalValorCte, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="tfoot-total">
                                    <td colspan="2">ACPM</td>
                                    <td style="text-align:right;">{{ number_format($totalGalonesAcpm, 3, ',', '.') }}
                                    </td>
                                    <td style="text-align:right;">{{ number_format($totalValorAcpm, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="tfoot-grand">
                                    <td colspan="2">Total venta</td>
                                    <td style="text-align:right;">
                                        {{ number_format($totalGalonesCte + $totalGalonesAcpm, 3, ',', '.') }}</td>
                                    <td style="text-align:right;">
                                        {{ number_format($totalVentaIapropiada, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="stack">
                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">Consignaciones</div>
                        </div>
                        <div class="card-body">
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>No. Consig.</th>
                                        <th style="text-align:right;">Valor</th>
                                        <th style="text-align:right;">Descuento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consignaciones as $index => $consignacion)
                                        <tr>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="consignaciones.{{ $index }}.numero"
                                                    placeholder="—">
                                            </td>
                                            <td style="width: 120px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="consignaciones.{{ $index }}.valor"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                            <td style="width: 120px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="consignaciones.{{ $index }}.descuento"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td>TOTALES</td>
                                        <td style="text-align:right;">
                                            {{ number_format($totalConsignaciones, 0, ',', '.') }}</td>
                                        <td style="text-align:right;">
                                            {{ number_format($totalDescuentos, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">Cartera - credito directo</div>
                        </div>
                        <div class="card-body">
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>No. Factura</th>
                                        <th>Cliente</th>
                                        <th style="text-align:right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cartera as $index => $item)
                                        <tr>
                                            <td style="width: 110px;">
                                                <input type="text" class="row-input"
                                                    wire:model.live="cartera.{{ $index }}.numero_factura"
                                                    placeholder="—">
                                            </td>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="cartera.{{ $index }}.cliente"
                                                    placeholder="Nombre cliente">
                                            </td>
                                            <td style="width: 120px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="cartera.{{ $index }}.valor"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td colspan="2">Total cartera</td>
                                        <td style="text-align:right;">{{ number_format($totalCartera, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <div class="grid-two">
                <section class="card">
                    <div class="card-header">
                        <div class="card-dot"></div>
                        <div class="card-title">Lectura electronica de surtidores</div>
                    </div>
                    <div class="card-body">
                        <table class="planilla">
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
                                @foreach ($lecturas as $index => $lectura)
                                    @php
                                        $galonesLectura = max(
                                            0,
                                            (float) ($lectura['final'] ?? 0) - (float) ($lectura['inicial'] ?? 0),
                                        );
                                    @endphp
                                    <tr>
                                        <td class="label-cell">{{ $lectura['manguera'] }}</td>
                                        <td>
                                            <span
                                                class="type-badge {{ $lectura['tipo'] === 'CTE' ? 'type-cte' : 'type-acpm' }}">{{ $lectura['tipo'] }}</span>
                                        </td>
                                        <td style="width: 110px;">
                                            <input type="text" inputmode="decimal" class="row-input"
                                                wire:model.live="lecturas.{{ $index }}.inicial" readonly>
                                        </td>
                                        <td style="width: 110px;">
                                            <input type="text" inputmode="decimal" class="row-input"
                                                wire:model.live="lecturas.{{ $index }}.final"
                                                placeholder="0,000" style="text-align:right;">
                                        </td>
                                        <td class="money" style="width: 100px;">
                                            {{ number_format($galonesLectura, 3, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="tfoot-total">
                                    <td colspan="2">Corriente - Galones</td>
                                    <td colspan="2"></td>
                                    <td style="text-align:right;">{{ number_format($lecturaGalonesCte, 3, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="tfoot-total">
                                    <td colspan="2">ACPM - Galones</td>
                                    <td colspan="2"></td>
                                    <td style="text-align:right;">
                                        {{ number_format($lecturaGalonesAcpm, 3, ',', '.') }}</td>
                                </tr>
                                <tr class="tfoot-total">
                                    <td colspan="2">Venta CTE</td>
                                    <td colspan="2" class="money">Precio:
                                        {{ number_format($precioCte, 0, ',', '.') }} / gl</td>
                                    <td style="text-align:right;">{{ number_format($lecturaValorCte, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="tfoot-total">
                                    <td colspan="2">Venta ACPM</td>
                                    <td colspan="2" class="money">Precio:
                                        {{ number_format($precioAcpm, 0, ',', '.') }} / gl</td>
                                    <td style="text-align:right;">{{ number_format($lecturaValorAcpm, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="tfoot-grand">
                                    <td colspan="4">Total lectura</td>
                                    <td style="text-align:right;">{{ number_format($totalVentaLectura, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="stack">
                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">TC, QR, Nequi y Daviplata</div>
                        </div>
                        <div class="card-body">
                            <div class="form-grid"
                                style="grid-template-columns: repeat(3, minmax(0, 1fr)); padding-bottom: 6px;">
                                <x-input-money class="field" :label="'Datafono 1'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="tc_datafono_1" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Datafono 2'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="tc_datafono_2" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Datafono 3'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="tc_datafono_3" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Transferencias'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="transferencias_bancolombia" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Gasolina EDS'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="gasolina_eds" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Puntos redimidos'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="puntos_redimidos" class="w-full">
                                </x-input-money>
                            </div>
                            <div class="wide-note note" style="padding: 0 16px 16px;">
                                Los medios de pago se consolidan en el resumen de lo recibido en el turno.
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">Recaudos y anticipos por islas</div>
                        </div>
                        <div class="card-body">
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th style="text-align:right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recaudos_anticipos as $index => $recaudo)
                                        <tr>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="recaudos_anticipos.{{ $index }}.cliente"
                                                    placeholder="Cliente">
                                            </td>
                                            <td style="width: 140px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="recaudos_anticipos.{{ $index }}.valor"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td>Total recaudos</td>
                                        <td style="text-align:right;">{{ number_format($totalRecaudos, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <div class="grid-two">
                <section class="card">
                    <div class="card-header">
                        <div class="card-dot"></div>
                        <div class="card-title">Ventas de urea y lubricantes</div>
                    </div>
                    <div class="card-body">
                        <table class="planilla">
                            <thead>
                                <tr>
                                    <th style="width: 90px;">Cant.</th>
                                    <th>Producto</th>
                                    <th style="text-align:right;">Sin IVA</th>
                                    <th style="text-align:right;">IVA</th>
                                    <th style="text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($urea_lubricantes as $index => $item)
                                    @php
                                        $totalLinea =
                                            ((float) ($item['cantidad'] ?? 0)) *
                                            (((float) ($item['valor_sin_iva'] ?? 0)) + ((float) ($item['iva'] ?? 0)));
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="text" inputmode="decimal" class="row-input"
                                                wire:model.live="urea_lubricantes.{{ $index }}.cantidad"
                                                placeholder="0" style="text-align:right;">
                                        </td>
                                        <td>
                                            <select class="row-select"
                                                wire:model.live="urea_lubricantes.{{ $index }}.producto">
                                                <option value="">Seleccione producto</option>
                                                @foreach ($catalogo_productos as $product)
                                                    <option value="{{ $product['codigo'] }}">{{ $product['nombre'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="width: 130px;">
                                            <input type="text" class="row-input"
                                                value="{{ number_format((float) ($item['valor_sin_iva'] ?? 0), 0, ',', '.') }}"
                                                readonly>
                                        </td>
                                        <td style="width: 130px;">
                                            <input type="text" class="row-input"
                                                value="{{ number_format((float) ($item['iva'] ?? 0), 0, ',', '.') }}"
                                                readonly>
                                        </td>
                                        <td class="money" style="width: 140px;">
                                            {{ number_format($totalLinea, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="tfoot-total">
                                    <td colspan="2">Totales</td>
                                    <td style="text-align:right;">{{ number_format($totalUreaSinIva, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align:right;">{{ number_format($totalUreaIva, 0, ',', '.') }}</td>
                                    <td style="text-align:right;">{{ number_format($totalUreaTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="stack">
                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">Varios</div>
                        </div>
                        <div class="card-body">
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th style="text-align:right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($varios as $index => $item)
                                        <tr>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="varios.{{ $index }}.concepto"
                                                    placeholder="Concepto">
                                            </td>
                                            <td style="width: 140px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="varios.{{ $index }}.valor"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td>Total varios</td>
                                        <td style="text-align:right;">{{ number_format($totalVarios, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>

                    <section class="card">
                        <div class="card-header">
                            <div class="card-dot"></div>
                            <div class="card-title">Recaudos por administracion</div>
                        </div>
                        <div class="card-body">
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>Banco / Caja</th>
                                        <th>Cliente</th>
                                        <th style="text-align:right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recaudos_administracion as $index => $item)
                                        <tr>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="recaudos_administracion.{{ $index }}.banco_caja"
                                                    placeholder="Banco o caja">
                                            </td>
                                            <td>
                                                <input type="text" class="row-input"
                                                    wire:model.live="recaudos_administracion.{{ $index }}.cliente"
                                                    placeholder="Cliente">
                                            </td>
                                            <td style="width: 140px;">
                                                <input type="text" inputmode="decimal" class="row-input"
                                                    wire:model.live="recaudos_administracion.{{ $index }}.valor"
                                                    placeholder="0" style="text-align:right;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td colspan="2">Total admin</td>
                                        <td style="text-align:right;">
                                            {{ number_format($totalRecaudosAdministracion, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card">
                <div class="card-header">
                    <div class="card-dot"></div>
                    <div class="card-title">Resumen del turno</div>
                </div>
                <div class="card-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
                    <div class="grid-two">
                        <div>
                            <div class="field-label" style="margin-bottom: 8px;">Resumen de lo vendido</div>
                            <table class="planilla">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th style="text-align:right;">S/ IAPROPIADA</th>
                                        <th style="text-align:right;">S/ Lectura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="label-cell">Combustible</td>
                                        <td class="money">{{ number_format($totalVentaIapropiada, 0, ',', '.') }}
                                        </td>
                                        <td class="money">{{ number_format($totalVentaLectura, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Urea / Lubricantes</td>
                                        <td class="money">{{ number_format($totalUreaTotal, 0, ',', '.') }}</td>
                                        <td class="money">{{ number_format($totalUreaTotal, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-grand">
                                        <td>Total vendido</td>
                                        <td class="money">{{ number_format($totalVendidoIapropiada, 0, ',', '.') }}
                                        </td>
                                        <td class="money">{{ number_format($totalVendidoLectura, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            <div style="height: 12px;"></div>
                            <div class="field-label" style="margin-bottom: 8px;">Faltantes / sobrantes</div>
                            <table class="planilla">
                                <tbody>
                                    <tr>
                                        <td class="label-cell">S/ IAPROPIADA</td>
                                        <td
                                            class="money {{ $faltanteSobranteIapropiada >= 0 ? 'text-green' : 'text-red' }}">
                                            {{ number_format($faltanteSobranteIapropiada, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">S/ Lectura</td>
                                        <td
                                            class="money {{ $faltanteSobranteLectura >= 0 ? 'text-green' : 'text-red' }}">
                                            {{ number_format($faltanteSobranteLectura, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <div class="field-label" style="margin-bottom: 8px;">Resumen de lo recibido</div>
                            <table class="planilla">
                                <tbody>
                                    <tr>
                                        <td class="label-cell">Consignaciones</td>
                                        <td class="money">{{ number_format($totalConsignaciones, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">TC / QR / Nequi</td>
                                        <td class="money">{{ number_format($totalTc, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Puntos redimidos</td>
                                        <td class="money">{{ number_format($puntos_redimidos, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Gasolina EDS</td>
                                        <td class="money">{{ number_format($gasolina_eds, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Transferencias</td>
                                        <td class="money">
                                            {{ number_format($transferencias_bancolombia, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Descuentos</td>
                                        <td class="money">{{ number_format($totalDescuentos, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Cartera</td>
                                        <td class="money">{{ number_format($totalCartera, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Varios</td>
                                        <td class="money">{{ number_format($totalVarios, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="tfoot-total">
                                        <td>Subtotal ingresos</td>
                                        <td class="money">{{ number_format($subtotalIngresos, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="tfoot-total">
                                        <td>Recaudos y anticipos</td>
                                        <td class="money">{{ number_format($totalRecaudos, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="tfoot-grand">
                                        <td>Total recibido</td>
                                        <td class="money">{{ number_format($totalRecibido, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="summary-grid">
                        <div class="metric">
                            <div class="metric-label">Total venta IAPROPIADA</div>
                            <div class="metric-value">{{ number_format($totalVendidoIapropiada, 0, ',', '.') }}</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Total venta por lectura</div>
                            <div class="metric-value">{{ number_format($totalVendidoLectura, 0, ',', '.') }}</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Total recibido en el turno</div>
                            <div class="metric-value big">{{ number_format($totalRecibido, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="grid-two">
                        <div>
                            <div class="field-label" style="margin-bottom: 8px;">Traslado a sobrantes / faltantes
                            </div>
                            <div class="form-grid" style="grid-template-columns: 1fr 1fr; padding: 0;">
                                <x-input-money class="field" :label="'Sobrante'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="traslado_sobrante" placeholder="0" class="w-full">
                                </x-input-money>
                                <x-input-money class="field" :label="'Faltante'">
                                    <input type="text" inputmode="decimal" data-decimals="0"
                                        wire:model.live="traslado_faltante" placeholder="0" class="w-full">
                                </x-input-money>
                            </div>
                            <p class="note" style="margin-top: 10px;">
                                Este bloque se deja disponible para la confirmacion administrativa del sobrante o
                                faltante.
                            </p>
                        </div>

                        <div>
                            <div class="field-label" style="margin-bottom: 8px;">Notas operativas</div>
                            <p class="note">
                                La lectura inicial se toma del ultimo turno guardado del mismo dia. Si no existe turno
                                previo, queda en cero.
                                Los valores de urea y lubricantes se alimentan desde un catalogo base que puedes
                                reemplazar por una tabla de precios.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="actions" style="padding-bottom: 18px;">
                <button type="button" class="btn btn-primary" wire:click="guardar"
                    wire:loading.attr="disabled">Guardar turno</button>
                <button type="button" class="btn btn-secondary" wire:click="nuevoTurno"
                    wire:loading.attr="disabled">Nuevo turno</button>
            </div>
        </div>
    </div>
</div>
