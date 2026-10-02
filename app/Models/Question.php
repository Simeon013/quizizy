<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'enonce', 'explication', 'indice', 'a_l_encre', 'ordre'])]
class Question extends Model
{
    protected function casts(): array
    {
        return ['a_l_encre' => 'boolean'];
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return HasMany<Choix, $this> */
    public function choix(): HasMany
    {
        return $this->hasMany(Choix::class)->orderBy('ordre');
    }
}
