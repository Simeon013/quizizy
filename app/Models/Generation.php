<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Le journal des générations de l'atelier : quota par jour et suivi du coût. */
#[Fillable(['user_id', 'quiz_id', 'modele', 'statut', 'questions', 'jetons_entree', 'jetons_sortie', 'erreur'])]
class Generation extends Model
{
    public const UPDATED_AT = null;
}
