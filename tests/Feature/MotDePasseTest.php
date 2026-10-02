<?php

use App\Models\User;
use App\Notifications\LienMotDePasse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(fn () => Notification::fake());

function demanderLien(User $user): string
{
    test()->post('/mot-de-passe-oublie', ['email' => $user->email])->assertSessionHas('succes');
    $jeton = null;
    Notification::assertSentTo($user, LienMotDePasse::class, function (LienMotDePasse $n) use (&$jeton) {
        $jeton = $n->jeton;

        return true;
    });

    return $jeton;
}

it('affiche les pages', function () {
    $this->get('/mot-de-passe-oublie')->assertOk()->assertInertia(fn (Page $p) => $p->component('Auth/Oubli'));
    $this->get('/mot-de-passe/abc?email=a@b.c')->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Auth/NouveauMotDePasse')->where('jeton', 'abc')->where('email', 'a@b.c'));
});

it('envoie un lien en français vers la page du nouveau mot de passe', function () {
    $user = User::factory()->create(['name' => 'Awa']);
    $jeton = demanderLien($user);

    $mail = (new LienMotDePasse($jeton))->toMail($user);
    expect($mail->subject)->toBe('Ton nouveau mot de passe Eurêka')
        ->and($mail->actionUrl)->toContain("/mot-de-passe/{$jeton}")
        ->and($mail->actionUrl)->toContain('email='.urlencode($user->email));
});

it('répond pareil pour une adresse inconnue, sans rien envoyer', function () {
    $this->post('/mot-de-passe-oublie', ['email' => 'personne@example.com'])
        ->assertSessionHas('succes', fn ($m) => str_starts_with($m, 'Si un cahier existe'));
    Notification::assertNothingSent();
});

it('change le mot de passe, ouvre le cahier et ferme les autres sessions', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'autre-appareil', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $jeton = demanderLien($user);

    $this->post('/mot-de-passe', [
        'jeton' => $jeton, 'email' => $user->email, 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret',
    ])->assertRedirect('/cahier');

    $this->assertAuthenticatedAs($user);
    expect(Hash::check('nouveau-secret', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'autre-appareil')->exists())->toBeFalse();
});

it('ne sert qu\'une fois', function () {
    $user = User::factory()->create();
    $jeton = demanderLien($user);
    $donnees = ['jeton' => $jeton, 'email' => $user->email, 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret'];

    $this->post('/mot-de-passe', $donnees);
    auth()->logout();
    $this->post('/mot-de-passe', [...$donnees, 'password' => 'encore-autre', 'password_confirmation' => 'encore-autre'])
        ->assertSessionHasErrors('email');
});

it('refuse un lien expiré ou inventé', function () {
    $user = User::factory()->create(['password' => 'ancien-secret']);
    $jeton = demanderLien($user);

    $this->post('/mot-de-passe', ['jeton' => 'invente', 'email' => $user->email, 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret'])
        ->assertSessionHasErrors('email');

    $this->travel(61)->minutes();
    $this->post('/mot-de-passe', ['jeton' => $jeton, 'email' => $user->email, 'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret'])
        ->assertSessionHasErrors('email');

    expect(Hash::check('ancien-secret', $user->fresh()->password))->toBeTrue();
    $this->assertGuest();
});

it('limite les demandes', function () {
    for ($i = 0; $i < 3; $i++) {
        $this->post('/mot-de-passe-oublie', ['email' => "x{$i}@example.com"]);
    }
    $this->post('/mot-de-passe-oublie', ['email' => 'x9@example.com'])->assertStatus(429);
});
