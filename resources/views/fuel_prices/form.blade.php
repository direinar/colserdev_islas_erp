<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Nombre
        </label>

        {{-- Fijo a "Gasolina"/"ACPM": son los nombres que FuelPrice::activePriceOn()
             busca al calcular el precio vigente de cada turno (ver TurnoController
             y planillas/turnos/create.blade.php). Un nombre libre rompería ese cálculo. --}}
        <select name="name" class="form-select" required>

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

        <label class="form-label">
            Precio
        </label>

        <input type="number" step="0.01" name="price" class="form-control"
            value="{{ old('price', $fuelPrice->price ?? '') }}" required>

    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Fecha Vigencia
        </label>

        <input type="date" name="effective_date" class="form-control"
            value="{{ old('effective_date', $fuelPrice->effective_date ?? '') }}">

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Estado
        </label>

        <select name="active" class="form-select">

            <option value="1" {{ old('active', $fuelPrice->active ?? 1) == 1 ? 'selected' : '' }}>

                Activo

            </option>

            <option value="0" {{ old('active', $fuelPrice->active ?? 1) == 0 ? 'selected' : '' }}>

                Inactivo

            </option>

        </select>

    </div>

</div>
