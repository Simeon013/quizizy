<?php

namespace App\Models;

use App\Support\Onglets;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une matière : un intercalaire du cahier (Sciences, Histoire…).
 */
#[Table('matieres')]
#[RouteKey('slug')]
#[Fillable(['nom', 'slug', 'description', 'onglet', 'ordre'])]
class Matiere extends Model
{
    /** @return HasMany<Quiz, $this> */
    public function quiz(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** Les quiz du catalogue public (les intercalaires). @return HasMany<Quiz, $this> */
    public function quizPublies(): HasMany
    {
        return $this->quiz()->where('publie', true)->where('au_catalogue', true);
    }

    /**
     * Ce que le front reçoit : jamais la couleur brute, toujours un onglet de la liste fermée.
     *
     * @return array<string, mixed>
     */
    public function resume(): array
    {
        return [
            'nom' => $this->nom,
            'slug' => $this->slug,
            'description' => $this->description,
            'onglet' => Onglets::couleur($this->onglet),
        ];
    }
}
