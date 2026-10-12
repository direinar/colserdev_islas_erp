<div class="row">

    <div class="col-md-6 mb-3">
        <label for="campo-name" class="form-label">Nombre</label>
        <input id="campo-name" type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>

    <div class="col-md-6 mb-3">
        <label for="campo-email" class="form-label">Email</label>
        <input id="campo-email" type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}"
            required>
    </div>

    <div class="col-md-6 mb-3">
        <label for="campo-role" class="form-label">Rol</label>
        <select id="campo-role" name="role" class="form-select" required>
            <option value="">Selecciona un rol</option>
            @foreach (auth()->user()->rolesAsignables() as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>
                    {{ App\Models\User::etiquetaRol($role) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="campo-password" class="form-label">Contraseña</label>
        <input id="campo-password" type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
        <div class="form-text">
            {{ isset($user) ? 'Dejar vacío para mantener la contraseña actual.' : 'Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.' }}</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="campo-password_confirmation" class="form-label">Confirmar contraseña</label>
        <input id="campo-password_confirmation" type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}>
    </div>

</div>
