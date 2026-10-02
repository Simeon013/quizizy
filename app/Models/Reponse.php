<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('reponses')]
#[Fillable(['partie_id', 'question_id', 'choix_id', 'juste', 'indice', 'points', 'duree_ms'])]
class Reponse extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['juste' => 'boolean', 'indice' => 'boolean'];
    }

    /** @return BelongsTo<Partie, $this> */
    public function partie(): BelongsTo
    {
        return $this->belongsTo(Partie::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
