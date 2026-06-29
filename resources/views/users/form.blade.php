<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}"
            required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Rol</label>
        <select name="role" class="form-select" required>
            <option value="">Selecciona un rol</option>
            @foreach (App\Models\User::roles() as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>
                    {{ ucfirst(str_replace('_', ' ', $role)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }}>
        <div class="form-text">
            {{ isset($user) ? 'Dejar vacío para mantener la contraseña actual.' : 'Mínimo 8 caracteres.' }}</div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}>
    </div>

</div>
