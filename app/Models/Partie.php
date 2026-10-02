<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une partie, autrement dit une copie. Son état est entièrement côté serveur :
 * le navigateur ne connaît ni les bonnes réponses ni l'heure d'affichage.
 */
#[Table('parties')]
#[Fillable(['user_id', 'quiz_id', 'type', 'questions', 'secondes_par_question'])]
class Partie extends Model
{
    use HasUlids;

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_REVISION = 'revision';

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'question_affichee_le' => 'datetime',
            'indice_pris' => 'boolean',
            'terminee_le' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return HasMany<Reponse, $this> */
    public function reponses(): HasMany
    {
        return $this->hasMany(Reponse::class);
    }

    public function total(): int
    {
        return count($this->questions);
    }

    public function estTerminee(): bool
    {
        return $this->terminee_le !== null;
    }

    public function questionEnCoursId(): ?int
    {
        return $this->estTerminee() ? null : ($this->questions[$this->position] ?? null);
    }

    public function titre(): string
    {
        return $this->type === self::TYPE_REVISION ? 'Révision' : ($this->quiz?->titre ?? 'Quiz');
    }
}
