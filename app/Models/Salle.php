<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une salle du mode en direct. Son état fait foi pour tous les écrans ;
 * `version` augmente à chaque changement.
 */
#[Table('salles')]
#[Fillable(['code', 'quiz_id', 'animateur_id', 'questions', 'secondes_par_question'])]
class Salle extends Model
{
    use HasUlids;

    public const ATTENTE = 'attente';

    public const QUESTION = 'question';

    public const CORRECTION = 'correction';

    public const TERMINEE = 'terminee';

    /** Une salle sans activité depuis ce délai est considérée comme fermée. */
    public const DUREE_DE_VIE_HEURES = 6;

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'question_debut_le' => 'datetime',
            'question_fin_le' => 'datetime',
            'terminee_le' => 'datetime',
        ];
    }

    /** @param Builder<Salle> $query */
    public function scopeOuvertes(Builder $query): void
    {
        $query->where('etat', '!=', self::TERMINEE)
            ->where('updated_at', '>=', now()->subHours(self::DUREE_DE_VIE_HEURES));
    }

    public function estOuverte(): bool
    {
        return $this->etat !== self::TERMINEE
            && $this->updated_at->gte(now()->subHours(self::DUREE_DE_VIE_HEURES));
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /** @return HasMany<Participant, $this> */
    public function presents(): HasMany
    {
        return $this->participants()->whereNull('retire_le');
    }

    /** @return HasMany<ReponseDirect, $this> */
    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseDirect::class);
    }

    public function total(): int
    {
        return count($this->questions);
    }

    public function questionEnCoursId(): ?int
    {
        return $this->questions[$this->position] ?? null;
    }

    public function estDerniere(): bool
    {
        return $this->position >= $this->total() - 1;
    }
}
