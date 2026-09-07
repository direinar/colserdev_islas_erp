<x-erp-card>

    {{-- Styles moved to resources/css/custom.css --}}

    <style>
        .canastilla-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: #b5fdd2;
            border-bottom: 1px solid #d0d0d0;
            min-height: 48px;
        }

        .canastilla-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #1d1d1d;
        }

        .canastilla-add-btn {
            background: #f3f3f3 !important;
            color: #d93a2f !important;
            border-color: #d9d9d9 !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 7px 14px !important;
        }

        .canastilla-add-btn:hover {
            background: #fff !important;
            border-color: #cfcfcf !important;
        }
    </style>

    <div class="canastilla-toolbar">
        <div class="canastilla-title">VENTA DE CANASTILLA - CONTADO Y CRÉDITO</div>
        <button type="button" id="add-lubricante-row" class="btn btn-sm btn-outline-primary canastilla-add-btn">+
            AGREGAR FILA</button>
    </div>

    <div class="table-responsive">

        <table class="table table-bordered table-sm lubricantes-table">
            <thead style="background-color: #b5fdd2;">
                {{-- <tr>
                    <th colspan="5" class="text-center">VENTAS SEGÚN CIERRES DE IAPROPIADA</th>
                </tr> --}}
                <tr>
                    <th style="width: 90px;">CANTIDAD</th>
                    <th>PRODUCTO</th>
                    <th class="text-end" style="width: 140px;">VR. SIN IVA</th>
                    <th class="text-end" style="width: 120px;">IVA</th>
                    <th class="text-end" style="width: 140px;">TOTAL</th>
                    <th class="text-center" style="width: 90px;">ACCIÓN</th>
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
                                    value="{{ number_format($l->valor_sin_iva, 0, ',', '.') }}" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $i }}][iva]"
                                    class="form-control form-control-sm iva-input"
                                    value="{{ number_format($l->iva, 0, ',', '.') }}" readonly />
                            </td>
                            <td>
                                <input type="text" name="urea_lubricantes[{{ $i }}][total]"
                                    class="form-control form-control-sm total-input"
                                    value="{{ number_format($l->total, 0, ',', '.') }}" readonly />
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
                    <td class="text-end" style="text-align: right;"><strong id="total-valor-sin-iva">0</strong></td>
                    <td class="text-end" style="text-align: right;"><strong id="total-iva">0</strong></td>
                    <td class="text-end" style="text-align: right;"><strong id="total-total">0</strong></td>
                    <td></td>
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
