<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Nombre
        </label>

        <input type="text"
               name="name"
               class="form-control"
               value="{{ old('name', $fuelPrice->name ?? '') }}"
               required>

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Precio
        </label>

        <input type="number"
               step="0.01"
               name="price"
               class="form-control"
               value="{{ old('price', $fuelPrice->price ?? '') }}"
               required>

    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Fecha Vigencia
        </label>

        <input type="date"
               name="effective_date"
               class="form-control"
               value="{{ old('effective_date', $fuelPrice->effective_date ?? '') }}">

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Estado
        </label>

        <select name="active"
                class="form-select">

            <option value="1"
                {{ old('active', $fuelPrice->active ?? 1) == 1 ? 'selected' : '' }}>

                Activo

            </option>

            <option value="0"
                {{ old('active', $fuelPrice->active ?? 1) == 0 ? 'selected' : '' }}>

                Inactivo

            </option>

        </select>

    </div>

</div>
