<?php

use App\Models\Partie;
use App\Models\User;
use Database\Seeders\ContenuSeeder;

beforeEach(fn () => $this->seed(ContenuSeeder::class));

it('crée un cahier et y range la copie faite en invité', function () {
    $id = demarrerPartie();

    $this->post('/inscription', [
        'name' => 'Kossi',
        'email' => 'kossi@example.com',
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
    ])->assertRedirect('/cahier');

    $user = User::where('email', 'kossi@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect(Partie::find($id)->user_id)->toBe($user->id);
});

it('ne laisse pas devenir administrateur par le formulaire', function () {
    $this->post('/inscription', [
        'name' => 'Malin',
        'email' => 'malin@example.com',
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
        'is_admin' => true,
    ]);

    expect(User::where('email', 'malin@example.com')->first()->is_admin)->toBeFalse();
});

it('ignore les robots qui remplissent le champ piège', function () {
    $this->post('/inscription', [
        'name' => 'Robot',
        'email' => 'robot@example.com',
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
        'site_web' => 'http://spam.example',
    ])->assertRedirect('/');

    expect(User::where('email', 'robot@example.com')->exists())->toBeFalse();
});

it('connecte et déconnecte', function () {
    $user = User::factory()->create(['password' => 'motdepasse']);

    $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/connexion', ['email' => $user->email, 'password' => 'motdepasse'])->assertRedirect('/cahier');
    $this->assertAuthenticatedAs($user);

    $this->post('/deconnexion')->assertRedirect('/');
    $this->assertGuest();
});

it('limite les tentatives de connexion', function () {
    $user = User::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $this->post('/connexion', ['email' => $user->email, 'password' => 'faux']);
    }
    $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])->assertStatus(429);
});
