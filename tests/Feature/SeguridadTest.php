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

test('el jefe de patios solo entra a la planilla, cartera - estado de cuenta y control de tanques', function () {
    $jefe = User::factory()->create(['role' => User::ROLE_JEFE_PATIOS]);

    foreach (['turnos.create', 'cartera.index', 'control-tanques.index'] as $ruta) {
        $this->actingAs($jefe)->get(route($ruta))->assertOk();
    }

    foreach (['compras.create', 'compras-lubricantes.create', 'comprobante-contable-compras.create', 'anticipo-bimestral.create', 'informes.anticipo-bimestral.index', 'informes.recaudos-cartera.index', 'informes.saldos.index', 'turnos.pendientes', 'customers.index', 'users.index'] as $ruta) {
        $this->actingAs($jefe)->get(route($ruta))->assertForbidden();
    }
});

test('el islero solo entra a la planilla de turnos', function () {
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($islero)->get(route('turnos.create'))->assertOk();

    foreach (['cartera.index', 'control-tanques.index', 'compras.create', 'turnos.pendientes', 'users.index'] as $ruta) {
        $this->actingAs($islero)->get(route($ruta))->assertForbidden();
    }
});

test('el gerente y el super administrador tienen el acceso del administrador', function (string $role) {
    $usuario = User::factory()->create(['role' => $role]);

    foreach (['turnos.pendientes', 'compras.create', 'informes.cierre.index', 'customers.index', 'users.index'] as $ruta) {
        $this->actingAs($usuario)->get(route($ruta))->assertOk();
    }
})->with([User::ROLE_GERENTE, User::ROLE_SUPERADMIN]);

test('solo el gerente o el super administrador gestionan esas cuentas y asignan esos roles', function () {
    $gerente = User::factory()->create(['role' => User::ROLE_GERENTE]);
    $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $islero = User::factory()->create(['role' => User::ROLE_ISLERO]);

    $this->actingAs($admin)->get(route('users.edit', $gerente))->assertForbidden();
    $this->actingAs($admin)->put(route('users.update', $gerente), [
        'name' => $gerente->name, 'email' => $gerente->email, 'role' => User::ROLE_ISLERO,
    ])->assertForbidden();
    $this->actingAs($admin)->delete(route('users.destroy', $superadmin))->assertForbidden();
    $this->actingAs($admin)->put(route('users.update', $islero), [
        'name' => $islero->name, 'email' => $islero->email, 'role' => User::ROLE_GERENTE,
    ])->assertSessionHasErrors('role');

    expect($gerente->fresh()->role)->toBe(User::ROLE_GERENTE)
        ->and($islero->fresh()->role)->toBe(User::ROLE_ISLERO);

    $this->actingAs($gerente)->put(route('users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email, 'role' => User::ROLE_SUPERADMIN,
    ])->assertSessionHasNoErrors();

    expect($admin->fresh()->role)->toBe(User::ROLE_SUPERADMIN);
});

test('no se le quita el rol al único gerente o super administrador', function () {
    $gerente = User::factory()->create(['role' => User::ROLE_GERENTE]);
    User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($gerente)->put(route('users.update', $gerente), [
        'name' => $gerente->name, 'email' => $gerente->email, 'role' => User::ROLE_ADMINISTRADOR,
    ])->assertSessionHasErrors('role');

    expect($gerente->fresh()->role)->toBe(User::ROLE_GERENTE);
});

test('las rutas por rol comparan el rol igual que el menú, sin importar mayúsculas', function () {
    $admin = User::factory()->create(['role' => 'Administrador']);

    $this->actingAs($admin)->get(route('users.index'))->assertOk();
});

test('el precio de combustible guarda solo los campos validados', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('fuel-prices.store'), [
        'name' => 'Gasolina', 'price' => '16400', 'turno_desde' => '5', 'effective_date' => '2026-10-01', 'active' => '1',
        'id' => 999, 'created_at' => '2000-01-01',
    ])->assertSessionHas('success', 'Precio de combustible creado correctamente.');

    $precio = FuelPrice::query()->sole();

    expect($precio->id)->not->toBe(999)
        ->and($precio->created_at->year)->not->toBe(2000)
        ->and((float) $precio->price)->toBe(16400.0)
        ->and($precio->turno_desde)->toBe(5);
});

test('un precio que ya liquidó planillas no se edita ni se elimina; uno sin planillas sí', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    $usado = FuelPrice::create(['name' => 'Gasolina', 'price' => 16500, 'turno_desde' => 1]);
    $siguiente = FuelPrice::create(['name' => 'Gasolina', 'price' => 17000, 'turno_desde' => 5]);
    Turno::create(['fecha' => '2026-10-01', 'numero_turno' => 3, 'nombre_vendedor' => 'Ana']);

    $this->actingAs($admin)->get(route('fuel-prices.index'))
        ->assertOk()
        ->assertSeeInOrder(['17.000,00', 'SIN PLANILLAS', '16.500,00', 'EN USO'], false)
        ->assertDontSee('Activo');
    $this->actingAs($admin)->get(route('fuel-prices.edit', $usado))->assertSessionHas('error');
    $this->actingAs($admin)->put(route('fuel-prices.update', $usado), ['name' => 'Gasolina', 'price' => '1', 'turno_desde' => '1'])
        ->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('fuel-prices.destroy', $usado))->assertSessionHas('error');

    expect((float) $usado->fresh()->price)->toBe(16500.0);

    $this->actingAs($admin)->get(route('fuel-prices.edit', $siguiente))
        ->assertOk()
        ->assertDontSee('name="active"', false);
    $this->actingAs($admin)->delete(route('fuel-prices.destroy', $siguiente))->assertSessionHas('success');

    expect(FuelPrice::query()->whereKey($siguiente->id)->exists())->toBeFalse();
});

test('un precio no puede regir desde un turno con planilla revisada', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Turno::create(['fecha' => '2026-10-01', 'numero_turno' => 4, 'nombre_vendedor' => 'Ana', 'revisado' => true]);
    Turno::create(['fecha' => '2026-10-01', 'numero_turno' => 6, 'nombre_vendedor' => 'Ana']);

    $this->actingAs($admin)->post(route('fuel-prices.store'), ['name' => 'ACPM', 'price' => '9900', 'turno_desde' => '4'])
        ->assertSessionHasErrors('turno_desde');
    // Una planilla guardada sin revisar sí puede tomar el precio nuevo al volver a guardarla.
    $this->actingAs($admin)->post(route('fuel-prices.store'), ['name' => 'ACPM', 'price' => '9900', 'turno_desde' => '5'])
        ->assertSessionHasNoErrors();

    expect(FuelPrice::query()->sole()->turno_desde)->toBe(5);
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

    turnoAbierto($admin, 3, '2026-09-25');
    $this->actingAs($admin)->post(route('turnos.store'), [
        'fecha' => '2026-09-25',
        'numero_turno' => 3,
        'recaudos' => [['cliente_id' => 999999, 'valor' => '10.000']],
    ])->assertSessionHasErrors('recaudos.0.cliente_id');

    // La planilla abierta quedó sin guardar.
    expect(Turno::query()->whereNotNull('sobrante_pendiente')->count())->toBe(0);
});
