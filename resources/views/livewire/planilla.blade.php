{{-- Styles moved to resources/css/custom.css --}}

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

{{-- Formatter JS moved to resources/js/galones.js and loaded via Vite (app.js) --}}
