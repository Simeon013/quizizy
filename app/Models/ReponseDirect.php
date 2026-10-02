<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('reponses_direct')]
#[Fillable(['salle_id', 'participant_id', 'question_id', 'choix_id', 'juste', 'points', 'duree_ms'])]
class ReponseDirect extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['juste' => 'boolean'];
    }
}
