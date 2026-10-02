<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un choix de réponse. `juste` ne part jamais vers le navigateur avant la réponse.
 */
#[Table('choix', timestamps: false)]
#[Fillable(['question_id', 'texte', 'juste', 'ordre'])]
class Choix extends Model
{
    protected function casts(): array
    {
        return ['juste' => 'boolean'];
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
