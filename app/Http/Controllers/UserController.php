<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($request->user()->rolesAsignables())],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(Request $request, User $user)
    {
        $this->autorizarGestion($request->user(), $user);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->autorizarGestion($request->user(), $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($request->user()->rolesAsignables())],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ]);

        // Quitarle el rol al último gerente/super administrador o al último administrador
        // dejaría el sistema sin quien lo administre.
        if ($user->tieneControlTotal() && ! in_array($validated['role'], User::rolesControlTotal(), true) && $this->esUltimo($user, User::rolesControlTotal())) {
            return back()->withInput()->withErrors(['role' => 'Es el único gerente o super administrador: asigne primero ese rol a otro usuario.']);
        }

        if ($user->isAdministrador() && ! in_array($validated['role'], User::rolesAdministrativos(), true) && $this->esUltimo($user, User::rolesAdministrativos())) {
            return back()->withInput()->withErrors(['role' => 'Es el único administrador: asigne primero el rol de administrador a otro usuario.']);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return redirect()->route('users.index')->with('error', 'No puede eliminar su propio usuario.');
        }

        $this->autorizarGestion($request->user(), $user);

        if (($user->tieneControlTotal() && $this->esUltimo($user, User::rolesControlTotal()))
            || ($user->isAdministrador() && $this->esUltimo($user, User::rolesAdministrativos()))) {
            return redirect()->route('users.index')->with('error', 'No se puede eliminar al único administrador.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Las cuentas del gerente y del super administrador solo las gestionan ellos mismos.
     */
    private function autorizarGestion(User $usuario, User $user): void
    {
        if ($user->tieneControlTotal() && ! $usuario->tieneControlTotal()) {
            abort(403, 'Solo el gerente o el super administrador pueden modificar esta cuenta.');
        }
    }

    /**
     * @param  list<string>  $roles
     */
    private function esUltimo(User $user, array $roles): bool
    {
        return ! User::query()
            ->whereIn('role', $roles)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
