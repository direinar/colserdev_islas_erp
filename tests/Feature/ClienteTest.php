<?php

use App\Models\Customer;
use App\Models\User;

test('los clientes se listan en orden alfabético con su D.V.', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
    Customer::create(['name' => 'ZULUAGA LTDA', 'document' => '900306424', 'dv' => '9']);
    Customer::create(['name' => 'ARKATEC SAS', 'document' => '901210900', 'dv' => '3']);

    $this->actingAs($admin)->get(route('customers.index'))
        ->assertOk()
        ->assertSee('D.V.')
        ->assertSeeInOrder(['ARKATEC SAS', '901210900', '3', 'ZULUAGA LTDA', '900306424', '9']);
});

test('si no se digita el D.V. se calcula del NIT con el algoritmo de la DIAN', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

    $this->actingAs($admin)->post(route('customers.store'), ['name' => 'BANCOLOMBIA', 'document' => '890.903.938'])
        ->assertRedirect(route('customers.index'));
    $this->actingAs($admin)->post(route('customers.store'), ['name' => 'OTRO', 'document' => '860034313', 'dv' => '5'])
        ->assertRedirect(route('customers.index'));

    expect(Customer::query()->where('name', 'BANCOLOMBIA')->value('dv'))->toBe('8')
        // El D.V. digitado se respeta.
        ->and(Customer::query()->where('name', 'OTRO')->value('dv'))->toBe('5')
        ->and(Customer::digitoVerificacion('860034313'))->toBe('7');

    $this->actingAs($admin)->post(route('customers.store'), ['name' => 'MAL', 'document' => '1', 'dv' => '12'])
        ->assertSessionHasErrors('dv');
});
