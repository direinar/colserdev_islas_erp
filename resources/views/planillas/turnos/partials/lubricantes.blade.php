<x-erp-card title="VENTA DE CANASTILLA - CONTADO Y CRÉDITO">

    {{-- Styles moved to resources/css/custom.css --}}

    <div class="d-flex justify-content-end mb-2">
        <button type="button" id="add-lubricante-row" class="btn btn-sm btn-outline-primary">+ Agregar fila</button>
    </div>

    <div class="table-responsive">

        <table class="table table-bordered table-sm lubricantes-table">
            <thead class="bg-yellow">
                <tr>
                    <th colspan="5" class="text-center">VENTAS SEGÚN CIERRES DE IAPROPIADA</th>
                </tr>
                <tr>
                    <th style="width: 90px;">CANTIDAD</th>
                    <th>PRODUCTO</th>
                    <th style="width: 140px;">VALOR TOTAL SIN IVA</th>
                    <th style="width: 120px;">IVA</th>
                    <th style="width: 140px;">TOTAL</th>
                    <th style="width: 90px;">ACCIÓN</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $productOptions = $lubricants ?? collect();
                @endphp

                @if (isset($turno) && optional($turno->lubricantes)->count())
                    @foreach ($turno->lubricantes as $i => $l)
                        <tr data-index="{{ $i }}">
                            <td>
                                <input type="number" name="urea_lubricantes[{{ $i }}][cantidad]"
                                    min="0" step="1" class="form-control form-control-sm cantidad-input"
                                    value="{{ $l->cantidad }}" />
                            </td>
                            <td>
                                <select name="urea_lubricantes[{{ $i }}][producto]"
                                    class="form-select form-select-sm lubricantes-producto-select">
                                    <option value="">Seleccione producto</option>
                                    @foreach ($productOptions as $product)
                                        <option value="{{ $product->reference }}"
                                            data-sale-price="{{ $product->sale_price ?? 0 }}"
                                            data-iva="{{ $product->iva ?? 0 }}"
                                            @if ($l->producto === $product->reference) selected @endif>{{ $product->reference }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $i }}][valor_sin_iva]"
                                    class="form-control form-control-sm valor-sin-iva-input"
                                    value="{{ number_format($l->valor_sin_iva, 0, '.', ',') }}" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $i }}][iva]"
                                    class="form-control form-control-sm iva-input"
                                    value="{{ number_format($l->iva, 0, '.', ',') }}" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $i }}][total]"
                                    class="form-control form-control-sm total-input"
                                    value="{{ number_format($l->total, 0, '.', ',') }}" readonly />
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endforeach
                @elseif ($productOptions->isEmpty())
                    <tr>
                        <td colspan="6" class="text-center py-4">No hay productos disponibles. Agrega productos desde
                            el CRUD de lubricantes.</td>
                    </tr>
                @else
                    @for ($index = 0; $index < 1; $index++)
                        <tr data-index="{{ $index }}">
                            <td>
                                <input type="number" name="urea_lubricantes[{{ $index }}][cantidad]"
                                    min="0" step="1" class="form-control form-control-sm cantidad-input" />
                            </td>
                            <td>
                                <select name="urea_lubricantes[{{ $index }}][producto]"
                                    class="form-select form-select-sm lubricantes-producto-select" disabled>
                                    <option value="">Seleccione producto</option>
                                    @foreach ($productOptions as $product)
                                        <option value="{{ $product->reference }}"
                                            data-sale-price="{{ $product->sale_price ?? 0 }}"
                                            data-iva="{{ $product->iva ?? 0 }}">{{ $product->reference }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $index }}][valor_sin_iva]"
                                    class="form-control form-control-sm valor-sin-iva-input" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $index }}][iva]"
                                    class="form-control form-control-sm iva-input" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $index }}][total]"
                                    class="form-control form-control-sm total-input" readonly />
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row">×</button>
                            </td>
                        </tr>
                    @endfor
                @endif
            </tbody>
            <tfoot>
                <tr style="background-color: #c9f0ad; font-size: 1.05rem;">
                    <td colspan="2" class="text-center"><strong>TOTAL VENTA</strong></td>
                    <td class="text-start"><strong id="total-valor-sin-iva">0</strong></td>
                    <td class="text-start"><strong id="total-iva">0</strong></td>
                    <td class="text-start"><strong id="total-total">0</strong></td>
                </tr>
            </tfoot>
        </table>

        {{-- plantilla de opciones para uso en JS al crear filas nuevas --}}
        <select id="lubricantes-options-template" class="d-none">
            <option value="">Seleccione producto</option>
            @foreach ($productOptions as $product)
                <option value="{{ $product->reference }}" data-sale-price="{{ $product->sale_price ?? 0 }}"
                    data-iva="{{ $product->iva ?? 0 }}">{{ $product->reference }}</option>
            @endforeach
        </select>

    </div>

    {{-- JS moved to resources/js/lubricantes.js and imported via resources/js/app.js --}}

</x-erp-card>
