<?php

namespace App\Atelier;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Log;

/**
 * Rédacteur appuyé sur Claude (SDK officiel). La réponse est contrainte par un
 * schéma JSON ; elle est ensuite vérifiée question par question (Nettoyage).
 */
class RedacteurClaude implements Redacteur
{
    public function __construct(
        private readonly string $cle,
        private readonly string $modele,
        private readonly string $effort,
    ) {}

    public function proposer(Demande $demande): Proposition
    {
        $client = new Client(apiKey: $this->cle);

        try {
            $message = $client->beta->messages->create(
                model: $this->modele,
                maxTokens: 16000,
                system: Consignes::SYSTEME,
                messages: [['role' => 'user', 'content' => Consignes::message($demande)]],
                outputConfig: [
                    'effort' => $this->effort,
                    'format' => ['type' => 'json_schema', 'schema' => Consignes::schema()],
                ],
                // Si le modèle décline pour des raisons de sécurité (faux positif sur
                // un texte d'histoire, par exemple), un modèle de repli reprend la demande.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (AuthenticationException $e) {
            Log::error('Atelier : clé API refusée', ['erreur' => $e->getMessage()]);
            throw new ErreurRedaction('La génération est mal configurée. Les questions peuvent être écrites à la main en attendant.');
        } catch (RateLimitException) {
            throw new ErreurRedaction('Gribouille est très sollicité en ce moment. Réessaie dans une minute.');
        } catch (BadRequestException $e) {
            Log::error('Atelier : requête refusée', ['erreur' => $e->getMessage()]);
            throw new ErreurRedaction('La demande n\'a pas pu être traitée. Raccourcis le texte source et réessaie.');
        } catch (APIStatusException|APIConnectionException $e) {
            Log::warning('Atelier : service indisponible', ['erreur' => $e->getMessage()]);
            throw new ErreurRedaction('Le service de rédaction ne répond pas. Réessaie dans quelques instants.');
        }

        if ($message->stopReason === 'refusal') {
            throw new ErreurRedaction('Ce texte n\'a pas pu être transformé en questions. Essaie avec un autre texte ou un autre thème.', 'refusee');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new ErreurRedaction('La réponse a été coupée. Demande moins de questions à la fois.');
        }

        $texte = '';
        foreach ($message->content as $bloc) {
            if ($bloc->type === 'text') {
                $texte .= $bloc->text;
            }
        }
        $donnees = json_decode($texte, true);
        if (! is_array($donnees) || ! is_array($donnees['questions'] ?? null)) {
            Log::warning('Atelier : réponse illisible', ['debut' => mb_substr($texte, 0, 200)]);
            throw new ErreurRedaction('La réponse reçue était illisible. Réessaie.');
        }

        return new Proposition(
            $donnees['questions'],
            $message->model,
            (int) $message->usage->inputTokens,
            (int) $message->usage->outputTokens,
        );
    }
}
