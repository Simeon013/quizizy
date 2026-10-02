<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un joueur d'une salle en direct : un pseudo, sans compte. */
#[Fillable(['salle_id', 'pseudo'])]
class Participant extends Model
{
    protected function casts(): array
    {
        return ['retire_le' => 'datetime'];
    }

    /** @return BelongsTo<Salle, $this> */
    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function estRetire(): bool
    {
        return $this->retire_le !== null;
    }
}
