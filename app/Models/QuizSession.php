<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'question_count',
        'score',
        'completed',
        'completed_at',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Get the user that owns the quiz session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that the quiz session belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the session questions for the quiz session.
     */
    public function sessionQuestions(): HasMany
    {
        return $this->hasMany(QuizSessionQuestion::class);
    }

    /**
     * Get the user responses for the quiz session.
     */
    public function userResponses(): HasMany
    {
        return $this->hasMany(UserResponse::class, 'quiz_session_id');
    }

    /**
     * Scope a query to only include completed quiz sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Scope a query to only include active (incomplete) quiz sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('completed', false);
    }
}
