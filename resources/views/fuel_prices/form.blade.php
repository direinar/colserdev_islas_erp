<div class="row">

    <div class="col-md-6 mb-3">

        <label for="campo-name" class="form-label">
            Nombre
        </label>

        {{-- Fijo a "Gasolina"/"ACPM": son los nombres que FuelPrice::precioParaTurno()
             busca al calcular el precio vigente de cada turno (ver TurnoController
             y planillas/turnos/create.blade.php). Un nombre libre rompería ese cálculo. --}}
        <select id="campo-name" name="name" class="form-select" required>

            <option value="">Seleccione...</option>

            <option value="Gasolina" {{ old('name', $fuelPrice->name ?? '') == 'Gasolina' ? 'selected' : '' }}>

                Gasolina (Corriente)

            </option>

            <option value="ACPM" {{ old('name', $fuelPrice->name ?? '') == 'ACPM' ? 'selected' : '' }}>

                ACPM

            </option>

        </select>

    </div>

    <div class="col-md-6 mb-3">

        <label for="campo-price" class="form-label">
            Precio
        </label>

        <input id="campo-price" type="number" step="0.01" name="price" class="form-control"
            value="{{ old('price', $fuelPrice->price ?? '') }}" required>

    </div>

</div>

<div class="row">

    {{-- El precio rige desde un número de turno (no desde una fecha): en un mismo día
         el precio puede cambiar entre un turno y otro. --}}
    <div class="col-md-4 mb-3">

        <label for="campo-turno_desde" class="form-label">
            Rige desde el turno N°
        </label>

        <input id="campo-turno_desde" type="number" min="1" step="1" name="turno_desde" class="form-control"
            value="{{ old('turno_desde', $fuelPrice->turno_desde ?? $siguienteTurno ?? '') }}" required
            aria-describedby="ayuda-turno_desde">

        <div id="ayuda-turno_desde" class="form-text">
            Primera planilla de turno que se liquida con este precio.
        </div>

    </div>

    <div class="col-md-4 mb-3">

        <label for="campo-effective_date" class="form-label">
            Fecha del cambio
        </label>

        <input id="campo-effective_date" type="date" name="effective_date" class="form-control"
            value="{{ old('effective_date', $fuelPrice->effective_date ?? '') }}" aria-describedby="ayuda-effective_date">

        <div id="ayuda-effective_date" class="form-text">
            Solo informativa.
        </div>

    </div>

    <div class="col-md-4 mb-3">

        <label for="campo-active" class="form-label">
            Estado
        </label>

        <select id="campo-active" name="active" class="form-select">

            <option value="1" {{ old('active', $fuelPrice->active ?? 1) == 1 ? 'selected' : '' }}>

                Activo

            </option>

            <option value="0" {{ old('active', $fuelPrice->active ?? 1) == 0 ? 'selected' : '' }}>

                Inactivo

            </option>

        </select>

    </div>

</div>
