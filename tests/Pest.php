<?php

use App\Models\Choix;
use App\Models\Partie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/**
 * Lance une partie sur le premier quiz du contenu et renvoie son identifiant.
 */
function demarrerPartie(string $slug = 'le-corps-humain'): string
{
    $reponse = test()->post("/quiz/{$slug}/parties");
    $reponse->assertRedirect();

    return basename(parse_url($reponse->headers->get('Location'), PHP_URL_PATH));
}

/** Répond à la question en cours, juste ou faux. */
function repondre(string $partieId, bool $juste = true): TestResponse
{
    $partie = Partie::findOrFail($partieId);
    if ($partie->question_affichee_le === null) {
        test()->postJson("/parties/{$partieId}/question")->assertOk();
    }
    $partie->refresh();
    $questionId = $partie->questionEnCoursId();
    $choix = Choix::where('question_id', $questionId)->where('juste', $juste)->firstOrFail();

    return test()->postJson("/parties/{$partieId}/reponse", ['question_id' => $questionId, 'choix_id' => $choix->id]);
}
