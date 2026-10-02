<?php

namespace App\Atelier;

/**
 * Rédacteur sans appel réseau, pour les tests et le développement local.
 */
class RedacteurFactice implements Redacteur
{
    public function proposer(Demande $demande): Proposition
    {
        $sujet = $demande->theme ?? $demande->titre;
        $questions = [];
        for ($i = 1; $i <= $demande->nombre; $i++) {
            $questions[] = [
                'enonce' => "Question {$i} sur « {$sujet} » ?",
                'choix' => ["Bonne réponse {$i}", "Leurre A{$i}", "Leurre B{$i}", "Leurre C{$i}"],
                'explication' => "Explication de la question {$i}.",
                'indice' => "Indice {$i}.",
            ];
        }

        return new Proposition($questions, 'factice');
    }
}
