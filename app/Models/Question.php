<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'enonce', 'explication', 'indice', 'ordre'])]
class Question extends Model
{
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
