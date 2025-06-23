<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'avatar',
        'total_xp',
        'level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the quiz sessions for the user.
     */
    public function quizSessions()
    {
        return $this->hasMany(QuizSession::class);
    }

    /**
     * Get the user's statistics.
     */
    public function stat()
    {
        return $this->hasOne(UserStat::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'total_xp' => 'integer',
            'level' => 'integer',
        ];
    }

    /**
     * Get the user's responses.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(UserResponse::class);
    }

    /**
     * Get the user's statistics.
     */
    public function stats(): HasMany
    {
        return $this->hasMany(UserStat::class);
    }

    /**
     * Get the user's answered questions.
     */
    public function answeredQuestions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, UserResponse::class, 'user_id', 'id', 'id', 'question_id');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }
}
