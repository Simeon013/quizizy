<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('gommettes', timestamps: false)]
#[Fillable(['user_id', 'cle', 'partie_id', 'obtenue_le'])]
class Gommette extends Model
{
    protected function casts(): array
    {
        return ['obtenue_le' => 'datetime'];
    }
}
