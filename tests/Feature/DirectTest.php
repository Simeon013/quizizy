<?php

use App\Models\Choix;
use App\Models\Participant;
use App\Models\Salle;
use App\Models\User;
use Database\Seeders\ContenuSeeder;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(function () {
    $this->seed(ContenuSeeder::class);
    $this->animateur = User::factory()->create();
});

function ouvrirSalle(): Salle
{
    test()->actingAs(test()->animateur)->post('/quiz/le-corps-humain/direct')->assertRedirect();

    return Salle::latest()->firstOrFail();
}

/** Fait entrer un joueur, dans sa propre session, et renvoie sa session. */
function entrer(Salle $salle, string $pseudo): array
{
    Session::flush();
    auth()->logout();
    test()->post('/rejoindre', ['code' => $salle->code, 'pseudo' => $pseudo])->assertRedirect("/direct/{$salle->id}");

    return Session::all();
}

function commande(Salle $salle, string $action)
{
    Session::flush();

    return test()->actingAs(test()->animateur)->postJson("/animer/{$salle->id}/{$action}");
}

function repondreEnDirect(Salle $salle, array $session, bool $juste)
{
    $salle->refresh();
    $choix = Choix::where('question_id', $salle->questionEnCoursId())->where('juste', $juste)->first();
    auth()->logout();

    return test()->withSession($session)->postJson("/direct/{$salle->id}/reponse", [
        'question_id' => $salle->questionEnCoursId(),
        'choix_id' => $choix->id,
    ]);
}

it('ouvre une salle avec un code à six chiffres, réservée aux comptes', function () {
    $this->post('/quiz/le-corps-humain/direct')->assertRedirect('/connexion');

    $salle = ouvrirSalle();
    expect($salle->code)->toMatch('/^\d{6}$/')->and($salle->etat)->toBe('attente')->and($salle->total())->toBe(8);

    $this->get("/animer/{$salle->id}")->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Direct/Ecran')->where('etat.code', $salle->code)->where('etat.etat', 'attente'));
});

it('cache l\'écran de l\'animateur aux autres comptes', function () {
    $salle = ouvrirSalle();
    $this->actingAs(User::factory()->create())->get("/animer/{$salle->id}")->assertNotFound();
    $this->postJson("/animer/{$salle->id}/suivante")->assertNotFound();
});

it('fait entrer des joueurs avec un code et un pseudo unique', function () {
    $salle = ouvrirSalle();
    entrer($salle, 'Awa');

    Session::flush();
    $this->post('/rejoindre', ['code' => $salle->code, 'pseudo' => 'awa'])->assertSessionHasErrors('pseudo');
    $this->post('/rejoindre', ['code' => '000000', 'pseudo' => 'Kossi'])->assertSessionHasErrors('code');
    $this->post('/rejoindre', ['code' => $salle->code, 'pseudo' => 'K'])->assertSessionHasErrors('pseudo');

    expect($salle->presents()->pluck('pseudo')->all())->toBe(['Awa']);
});

it('renvoie vers « Rejoindre » un téléphone qui n\'est pas dans la salle', function () {
    $salle = ouvrirSalle();
    Session::flush();
    auth()->logout();
    $this->get("/direct/{$salle->id}")->assertRedirect("/rejoindre?code={$salle->code}");
    $this->getJson("/direct/{$salle->id}/etat")->assertNotFound();
});

it('déroule une partie : question, réponses, correction, palmarès, podium', function () {
    $salle = ouvrirSalle();
    $awa = entrer($salle, 'Awa');
    $kossi = entrer($salle, 'Kossi');

    commande($salle, 'suivante')->assertOk()->assertJsonPath('etat', 'question');

    // Pendant la question, ni le téléphone ni l'écran ne connaissent la bonne réponse.
    auth()->logout();
    $etat = $this->withSession($awa)->getJson("/direct/{$salle->id}/etat")->assertOk()->json();
    expect($etat['etat'])->toBe('question')
        ->and($etat['question']['choix'])->toHaveCount(4)
        ->and($etat['question']['choix'][0])->not->toHaveKey('juste')
        ->and($etat['correction'])->toBeNull()
        ->and($etat['moi']['points'])->toBeNull();

    repondreEnDirect($salle, $awa, juste: true)->assertOk();
    repondreEnDirect($salle, $awa, juste: true)->assertConflict();
    expect($salle->fresh()->etat)->toBe('question');

    // Le dernier joueur répond : la question se corrige sans attendre la fin du crayon.
    repondreEnDirect($salle, $kossi, juste: false)->assertOk();
    expect($salle->fresh()->etat)->toBe('correction');

    $vueAwa = $this->withSession($awa)->getJson("/direct/{$salle->id}/etat")->json();
    expect($vueAwa['correction']['juste'])->toBeTrue()
        ->and($vueAwa['correction']['points'])->toBeGreaterThan(100)
        ->and($vueAwa['moi']['rang'])->toBe(1);
    $vueKossi = $this->withSession($kossi)->getJson("/direct/{$salle->id}/etat")->json();
    expect($vueKossi['correction']['juste'])->toBeFalse()->and($vueKossi['moi']['rang'])->toBe(2);

    $ecran = commande($salle, 'suivante')->json();
    expect($ecran['etat'])->toBe('question')->and($ecran['question']['numero'])->toBe(2);

    // On saute à la fin.
    for ($i = 2; $i <= 8; $i++) {
        commande($salle, 'corriger')->assertOk();
        commande($salle, 'suivante')->assertOk();
    }
    expect($salle->fresh()->etat)->toBe('terminee');

    auth()->logout();
    $fin = $this->withSession($awa)->getJson("/direct/{$salle->id}/etat")->json();
    expect($fin['etat'])->toBe('terminee')->and($fin['podium'])->toHaveCount(2)->and($fin['podium'][0]['pseudo'])->toBe('Awa');
});

it('corrige d\'elle-même une question dont le temps est écoulé', function () {
    $salle = ouvrirSalle();
    $awa = entrer($salle, 'Awa');
    entrer($salle, 'Kossi');
    commande($salle, 'suivante');

    $this->travel(35)->seconds();
    repondreEnDirect($salle, $awa, juste: true)->assertConflict();

    auth()->logout();
    $etat = $this->withSession($awa)->getJson("/direct/{$salle->id}/etat")->json();
    expect($etat['etat'])->toBe('correction')
        ->and($etat['correction']['repondu'])->toBeFalse()
        ->and($etat['correction']['points'])->toBe(0);
});

it('ne renvoie que la version quand rien n\'a changé', function () {
    $salle = ouvrirSalle();
    $awa = entrer($salle, 'Awa');
    $version = $salle->fresh()->version;

    auth()->logout();
    $this->withSession($awa)->getJson("/direct/{$salle->id}/etat?v={$version}")
        ->assertExactJson(['version' => $version, 'inchange' => true]);
});

it('laisse l\'animateur retirer un pseudo', function () {
    $salle = ouvrirSalle();
    $intrus = entrer($salle, 'Pseudo déplacé');
    $p = Participant::where('pseudo', 'Pseudo déplacé')->first();

    Session::flush();
    $this->actingAs($this->animateur)->deleteJson("/animer/{$salle->id}/participants/{$p->id}")
        ->assertOk()->assertJsonCount(0, 'joueurs');

    auth()->logout();
    expect($this->withSession($intrus)->getJson("/direct/{$salle->id}/etat")->json('moi.retire'))->toBeTrue();
});

it('ne rouvre pas une salle terminée et libère son code', function () {
    $salle = ouvrirSalle();
    commande($salle, 'terminer');

    Session::flush();
    auth()->logout();
    $this->post('/rejoindre', ['code' => $salle->code, 'pseudo' => 'Tard'])->assertSessionHasErrors('code');
    expect(Salle::ouvertes()->where('code', $salle->code)->exists())->toBeFalse();
});

it('limite les appels par session et non par adresse IP', function () {
    $salle = ouvrirSalle();
    $awa = entrer($salle, 'Awa');
    $kossi = entrer($salle, 'Kossi');

    auth()->logout();
    for ($i = 0; $i < 150; $i++) {
        $this->withSession($awa)->getJson("/direct/{$salle->id}/etat");
    }
    $this->withSession($awa)->getJson("/direct/{$salle->id}/etat")->assertStatus(429);
    // Même adresse IP, autre téléphone : pas bloqué.
    $this->withSession($kossi)->getJson("/direct/{$salle->id}/etat")->assertOk();
});
