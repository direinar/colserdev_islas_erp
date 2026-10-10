<?php

use App\Models\CarteraMovimiento;
use App\Models\Customer;
use App\Models\FuelPrice;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('un cliente con historial de cartera no se puede eliminar', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $conCartera = Customer::create(['name' => 'Con cartera', 'document' => '1']);
    $sinMovimientos = Customer::create(['name' => 'Sin movimientos', 'document' => '2']);
    CarteraMovimiento::create(['customer_id' => $conCartera->id, 'origen' => CarteraMovimiento::ORIGEN_CREDITO_DIRECTO, 'fecha' => '2026-09-25', 'vr_neto_cargo' => 50000]);

    $this->actingAs($admin)->delete(route('customers.destroy', $conCartera))
        ->assertRedirect(route('customers.index'))
        ->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('customers.destroy', $sinMovimientos))
        ->assertSessionHas('success');

    expect(Customer::query()->whereKey($conCartera->id)->exists())->toBeTrue()
        ->and(CarteraMovimiento::query()->where('customer_id', $conCartera->id)->count())->toBe(1)
        ->and(Customer::query()->whereKey($sinMovimientos->id)->exists())->toBeFalse();
});

test('nadie se elimina a sí mismo ni deja el sistema sin administradores', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHas('error', 'No puede eliminar su propio usuario.');

    // Único administrador: no se le puede quitar el rol.
    $this->actingAs($admin)->put(route('users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email, 'role' => User::ROLE_JEFE_PATIOS,
    ])->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(User::ROLE_ADMINISTRADOR);

    // Con otro administrador, sí se puede eliminar uno de los dos.
    $otroAdmin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $this->actingAs($admin)->delete(route('users.destroy', $otroAdmin))->assertSessionHas('success');
    $this->actingAs($admin)->delete(route('users.destroy', $jefe))->assertSessionHas('success');

    expect(User::query()->where('role', User::ROLE_ADMINISTRADOR)->count())->toBe(1);
});

test('las rutas por rol comparan el rol igual que el menú, sin importar mayúsculas', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);

    $this->actingAs($admin)->get(route('users.index'))->assertOk();
});

test('el precio de combustible guarda solo los campos validados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('fuel-prices.store'), [
        'name' => 'Gasolina', 'price' => '16400', 'effective_date' => '2026-10-01', 'active' => '1',
        'id' => 999, 'created_at' => '2000-01-01',
    ])->assertSessionHas('success', 'Precio de combustible creado correctamente.');

    $precio = FuelPrice::query()->sole();

    expect($precio->id)->not->toBe(999)
        ->and($precio->created_at->year)->not->toBe(2000)
        ->and((float) $precio->price)->toBe(16400.0);
});

test('el seeder de administradores no cambia la clave de un usuario existente', function () {
    $existente = User::factory()->create([
        'email' => 'diego.reina9@hotmail.com',
        'password' => 'MiClaveSegura#2026',
        'role' => User::ROLE_ADMINISTRADOR,
    ]);

    $this->seed(AdminUserSeeder::class);

    expect(Hash::check('MiClaveSegura#2026', $existente->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $existente->fresh()->password))->toBeFalse()
        // El otro administrador se crea con una clave aleatoria, no con "password".
        ->and(Hash::check('password', User::query()->where('email', 'carlosbeltranramos@gmail.com')->sole()->password))->toBeFalse();
});

test('la planilla rechaza un cliente que no existe', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-09-25',
        'numero_turno' => 3,
        'recaudos' => [['cliente_id' => 999999, 'valor' => '10.000']],
    ])->assertSessionHasErrors('recaudos.0.cliente_id');

    expect(Turno::count())->toBe(0);
});
