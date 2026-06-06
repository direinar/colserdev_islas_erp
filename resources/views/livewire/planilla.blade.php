<style>
    /* Hide number input spinners */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }

    /* Numeric text inputs - tabular number formatting */

    <div class="mt-2 border border-[#8c7a56] bg-[#fffaf0]"><div class="border-b border-[#8c7a56] bg-[#efefef] px-3 py-2 text-[13px] font-bold uppercase tracking-[0.16em] text-[#2f240f]">RESUMEN DE LO RECIBIDO EN ESTA PLANILLA </div><table class="w-full border-collapse text-[12px]"><tbody>@foreach ($resumenIngresos as $index => $registro)
        <tr class="bg-white"><td class="border border-[#8c7a56] px-2 py-2 uppercase text-[#173b84]"><input type="text" wire:model.live="resumenIngresos.{{ $index }}.concepto" class="w-full border-0 bg-transparent px-0 py-0 font-semibold uppercase text-[#173b84] outline-none focus:ring-0"></td><td class="border border-[#8c7a56] px-2 py-2 text-right font-semibold text-[#2f240f]"><input type="text" inputmode="decimal" wire:model.live="resumenIngresos.{{ $index }}.valor" class="w-full border-0 bg-transparent px-0 py-0 text-right font-semibold text-[#2f240f] outline-none focus:ring-0"></td></tr>
    @endforeach

    <tr class="bg-[#fffdf5] font-bold text-[#2f240f]"><td class="border border-[#8c7a56] px-2 py-2 uppercase">SUBTOTAL INGRESOS</td><td class="border border-[#8c7a56] px-2 py-2 text-right">${{ number_format($this->subtotalIngresos, 0, ',', '.') }}</td></tr><tr class="bg-[#fffdf5] font-bold text-[#2f240f]"><td class="border border-[#8c7a56] px-2 py-2 uppercase">RECUDOS Y ANTICIPOS</td><td class="border border-[#8c7a56] px-2 py-2 text-right"><input type="text" inputmode="decimal" wire:model.live="recibosYAnticipos" class="w-full border-0 bg-transparent px-0 py-0 text-right font-bold text-[#2f240f] outline-none focus:ring-0"></td></tr><tr class="bg-[#d9d9d9] font-bold text-[#2f240f]"><td class="border border-[#8c7a56] px-2 py-2 uppercase text-[14px]">VENTA SEGÚN PLANILLA</td><td class="border border-[#8c7a56] px-2 py-2 text-right text-[14px]">${{ number_format($this->ventaSegunPlanilla, 0, ',', '.') }}</td></tr><tr class="bg-[#e98b1f] font-bold text-[#2f240f]"><td class="border border-[#8c7a56] px-2 py-2 uppercase">SOBRANTE O FALTANTE SEGUN CIERRES DE IAPROPIADA</td><td class="border border-[#8c7a56] px-2 py-2 text-right">{{ number_format($this->sobranteFaltanteSegunCierres, 0, ',', '.') }}</td></tr><tr class="bg-[#fffdf5] font-bold text-[#2f240f]"><td class="border border-[#8c7a56] px-2 py-2 uppercase">NOMBRE DEL VENDEDOR:</td><td class="border border-[#8c7a56] px-2 py-2 text-right uppercase">{{ $islero ?: '—' }}</td></tr><tr class="bg-[#6d8b15] font-bold text-[#172b05]"><td class="border border-[#8c7a56] px-2 py-2 uppercase">SOBRANTE O FALTANTE x LECTURA SURTIDORES</td><td class="border border-[#8c7a56] px-2 py-2 text-right text-[#dc2626]">{{ number_format($this->sobranteFaltanteLecturaSurtidores, 0, ',', '.') }}</td></tr></tbody></table></div>input[inputmode="decimal"] {
        font-variant-numeric: tabular-nums;
        font-family: 'Courier New', monospace;
    }
</style>

<div class="min-h-screen bg-[#d8d1c2] text-slate-900">
    <div class="mx-auto max-w-475 px-3 py-3 sm:px-4 lg:px-6">
        @if (session('success'))
            <div
                class="mb-4 rounded border border-emerald-700/30 bg-emerald-100 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden border border-[#8c7a56] bg-[#efe2c8] shadow-[0_4px_20px_rgba(0,0,0,0.12)]">
            <div class="grid grid-cols-1 gap-px bg-[#8c7a56]">
                <section class="bg-[#f8efdc] p-0">
                    <div
                        class="flex items-center justify-between border-b border-[#8c7a56] bg-[#e8c07a] px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-[#3d2f16]">
                        <span>Lectura según cierres</span>
                        <button type="button" wire:click="save"
                            class="rounded border border-[#7a632d] bg-[#f7e2a6] px-3 py-1 text-[11px] font-semibold text-[#3d2f16] shadow-sm">
                            Guardar planilla
                        </button>
                    </div>

                    <div class="border-b border-[#8c7a56] bg-[#f6ecd3] px-3 py-2 text-[12px] text-[#5d4c2b]">
                        Planilla actual: <span class="font-bold">{{ $planillaId ? '#' . $planillaId : 'Nueva' }}</span>
                        | Fecha: <span class="font-bold">{{ $fecha }}</span> | Turno: <span
                            class="font-bold">{{ $turno }}</span>
                    </div>

                    <div class="grid gap-px bg-[#8c7a56] text-[12px]">
                        <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#ebd39b] text-[#3d2f16]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 text-left font-bold">Surtidor</div>
                            <div class="border-r border-[#8c7a56] px-2 py-2 text-right font-bold">Galones</div>
                            <div class="px-2 py-2 text-right font-bold">Valor</div>
                        </div>

                        @foreach ($surtidores as $index => $surtidor)
                            <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#fffaf0]">
                                <div class="border-r border-[#8c7a56] px-2 py-1 font-semibold text-[#3d2f16]">
                                    {{ $surtidor['nombre'] }}
                                </div>
                                <div class="border-r border-[#8c7a56] px-1 py-1">
                                    <input type="text" inputmode="decimal"
                                        wire:model.live="surtidores.{{ $index }}.galones"
                                        class="w-full border-0 bg-transparent px-1 py-1 text-right text-[#3d2f16] outline-none focus:ring-0">
                                </div>
                                <div class="px-2 py-1 text-right font-semibold text-[#3d2f16]">
                                    ${{ number_format(max((float) ($surtidor['galones'] ?? 0), 0) * (float) ($precios[$surtidor['producto']] ?? 0), 2, ',', '') }}
                                </div>
                            </div>
                        @endforeach

                        <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#d6d6d6] font-bold text-[#2f240f]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Venta en tirillas cortes</div>
                            <div class="border-r border-[#8c7a56] px-2 py-2 text-center uppercase">Corriente</div>
                            <div class="px-2 py-2 text-center uppercase">ACPM</div>
                        </div>

                        <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#e8c07a] font-bold text-[#2f240f]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Galones</div>
                            <div class="border-r border-[#8c7a56] px-2 py-2 text-right">
                                {{ number_format($this->totalGalonesCorriente, 3, ',', '.') }}</div>
                            <div class="px-2 py-2 text-right">{{ number_format($this->totalGalonesAcpm, 3, ',', '.') }}
                            </div>
                        </div>

                        <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#f0f0f0] font-bold text-[#2f240f]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Valor</div>
                            <div class="border-r border-[#8c7a56] px-2 py-2 text-right">
                                {{ number_format($this->totalVentaCorriente, 2, ',', '.') }}</div>
                            <div class="px-2 py-2 text-right">{{ number_format($this->totalVentaAcpm, 2, ',', '.') }}
                            </div>
                        </div>

                        <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#b4d06c] font-bold text-[#203a11]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Precios</div>
                            <div class="border-r border-[#8c7a56] bg-[#edf8e2] px-1 py-1">
                                <input type="text" inputmode="decimal" wire:model.live="precios.CORRIENTE"
                                    class="w-full border-0 bg-transparent px-1 py-1 text-right text-[#203a11] outline-none focus:ring-0">
                            </div>
                            <div class="bg-[#edf8e2] px-1 py-1">
                                <input type="text" inputmode="decimal" wire:model.live="precios.ACPM"
                                    class="w-full border-0 bg-transparent px-1 py-1 text-right text-[#203a11] outline-none focus:ring-0">
                            </div>
                        </div>

                        <div class="grid grid-cols-[1.8fr_2fr] bg-[#e8a23b] font-bold text-[#2f240f]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Venta según lectura
                            </div>
                            <div class="px-2 py-2 text-right">
                                {{ number_format($this->ventaSegunCortesIapropiada, 2, ',', '.') }}</div>
                        </div>

                        <div class="grid grid-cols-[1.8fr_2fr] bg-zinc-100 font-bold text-[#2f240f]">
                            <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Diferencia en venta según lectura
                            </div>
                            <div class="px-2 py-2 text-right">
                                {{ number_format($this->diferenciaVentaSegunCortesIapropiada, 2, ',', '.') }}</div>
                        </div>
                    </div>

                    <div
                        class="border-t border-[#8c7a56] bg-[#f4db87] px-3 py-2 text-[12px] font-bold uppercase tracking-[0.15em] text-[#2f240f]">
                        Lectura electrónica de surtidores
                    </div>
                    <table class="w-full border-collapse text-[12px]">
                        <thead>
                            <tr class="bg-[#9fc24d] font-bold text-[#203a11]">
                                <th class="border border-[#8c7a56] px-2 py-2 text-left uppercase">Manguera</th>
                                <th class="border border-[#8c7a56] px-2 py-2 text-right uppercase">Inicial</th>
                                <th class="border border-[#8c7a56] px-2 py-2 text-right uppercase">Final</th>
                                <th class="border border-[#8c7a56] px-2 py-2 text-right uppercase">GLS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($surtidores as $surtidor)
                                <tr class="bg-[#fffaf0] text-[#3d2f16]">
                                    <td class="border border-[#8c7a56] px-2 py-2 font-semibold uppercase">
                                        {{ $surtidor['nombre'] }}</td>
                                    <td class="border border-[#8c7a56] px-2 py-2 text-right">
                                        {{ number_format((float) ($surtidor['lectura_inicial'] ?? 0), 3, ',', '.') }}
                                    </td>
                                    <td class="border border-[#8c7a56] px-2 py-2 text-right">
                                        {{ number_format((float) ($surtidor['lectura_final'] ?? 0), 3, ',', '.') }}
                                    </td>
                                    <td class="border border-[#8c7a56] px-2 py-2 text-right">
                                        {{ number_format(max((float) ($surtidor['galones'] ?? 0), 0), 3, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div
                        class="grid grid-cols-[1.8fr_1fr_1fr] border-t-2 border-[#8c7a56] bg-[#8c7a56] text-[12px] font-bold text-[#203a11]">
                        <div class="border-r border-[#8c7a56] bg-[#efe9cf] px-2 py-2 uppercase">Venta según lectura
                        </div>
                        <div class="border-r border-[#8c7a56] bg-[#efe9cf] px-2 py-2 text-center uppercase">Corriente
                        </div>
                        <div class="bg-[#efe9cf] px-2 py-2 text-center uppercase">ACPM</div>
                    </div>

                    <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#6f891f] text-[12px] font-bold text-[#203a11]">
                        <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Galones</div>
                        <div class="border-r border-[#8c7a56] px-2 py-2 text-right text-[#203a11]">
                            {{ number_format($this->totalGalonesCorriente, 3, ',', '.') }}
                        </div>
                        <div class="px-2 py-2 text-right text-[#203a11]">
                            {{ number_format($this->totalGalonesAcpm, 3, ',', '.') }}
                        </div>
                    </div>

                    <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#fffaf0] text-[12px] font-bold text-[#2f240f]">
                        <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Valor</div>
                        <div class="border-r border-[#8c7a56] px-2 py-2 text-right">
                            {{ number_format($this->totalVentaCorriente, 3, ',', '.') }}
                        </div>
                        <div class="px-2 py-2 text-right">
                            {{ number_format($this->totalVentaAcpm, 3, ',', '.') }}
                        </div>
                    </div>

                    <div class="grid grid-cols-[1.8fr_1fr_1fr] bg-[#d1eb9a] text-[12px] font-bold text-[#1f6f1f]">
                        <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Precios</div>
                        <div class="border-r border-[#8c7a56] px-2 py-2 text-right text-[#16a34a]">
                            {{ number_format($this->precioPorProducto('CORRIENTE'), 3, ',', '.') }}
                        </div>
                        <div class="px-2 py-2 text-right text-[#16a34a]">
                            {{ number_format($this->precioPorProducto('ACPM'), 3, ',', '.') }}
                        </div>
                    </div>

                    <div class="grid grid-cols-[1.8fr_2fr] bg-[#e6a231] font-bold text-[#2f240f]">
                        <div class="border-r border-[#8c7a56] px-2 py-2 uppercase">Venta según lectura de surtidores
                        </div>
                        <div class="px-2 py-2 text-right">
                            {{ number_format($this->ventaSegunCortesIapropiada, 2, ',', '.') }}
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', setupInputFormatting);
    document.addEventListener('livewire:updated', setupInputFormatting);

    function setupInputFormatting() {
        // Función para obtener el valor limpio (convertir coma a punto)
        function getCleanValue(value) {
            return value.replace(/[^\d.,]/g, '').replace(',', '.');
        }

        // Configurar inputs de galones
        document.querySelectorAll('input[wire\\:model\\.live*="galones"]').forEach(input => {
            setupNumericInput(input, true);
        });

        // Configurar inputs de precios
        document.querySelectorAll('input[wire\\:model\\.live*="precios"]').forEach(input => {
            setupNumericInput(input, true);
        });

        // Configurar inputs de medios de pago y cartera
        document.querySelectorAll('input[inputmode="decimal"]').forEach(input => {
            if (!input.hasAttribute('data-formatter-applied')) {
                setupNumericInput(input, false);
                input.setAttribute('data-formatter-applied', 'true');
            }
        });

        function setupNumericInput(input, formatDisplay = true) {
            if (input.hasAttribute('data-formatter-applied')) {
                return;
            }
            input.setAttribute('data-formatter-applied', 'true');

            // Formatear al perder el foco
            input.addEventListener('blur', function() {
                if (formatDisplay && this.value) {
                    const clean = getCleanValue(this.value);
                    if (clean) {
                        this.value = new Intl.NumberFormat('es-CO', {
                            minimumFractionDigits: 3,
                            maximumFractionDigits: 3
                        }).format(parseFloat(clean));
                    }
                }
            });

            // Permitir solo números, punto y coma
            input.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                if (!/[\d.,]/.test(char)) {
                    e.preventDefault();
                }
            });

            // Prevenir caracteres no válidos en paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const clean = paste.replace(/[^\d.,]/g, '');
                if (clean) {
                    document.execCommand('insertText', false, clean);
                }
            });
        }
    }
</script>
