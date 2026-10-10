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
            'role' => ['required', Rule::in(User::roles())],
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

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(User::roles())],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ]);

        // Quitarle el rol al único administrador dejaría el sistema sin quien lo administre.
        if ($user->isAdministrador() && $validated['role'] !== User::ROLE_ADMINISTRADOR && $this->esUltimoAdministrador($user)) {
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

        if ($user->isAdministrador() && $this->esUltimoAdministrador($user)) {
            return redirect()->route('users.index')->with('error', 'No se puede eliminar al único administrador.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    private function esUltimoAdministrador(User $user): bool
    {
        return ! User::query()
            ->where('role', User::ROLE_ADMINISTRADOR)
            ->whereKeyNot($user->id)
            ->exists();
    }
}
