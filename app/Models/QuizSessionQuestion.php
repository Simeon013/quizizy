<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizSessionQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quiz_session_id',
        'question_id',
        'answered',
        'is_correct',
        'answered_at',
        'time_taken',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'answered' => 'boolean',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    /**
     * Get the quiz session that owns the quiz session question.
     */
    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    /**
     * Get the question that is associated with the quiz session question.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Mark the question as answered.
     *
     * @param  bool  $isCorrect
     * @param  int|null  $timeTaken
     * @return $this
     */
    public function markAsAnswered(bool $isCorrect, ?int $timeTaken = null): self
    {
        $this->update([
            'answered' => true,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
            'time_taken' => $timeTaken,
        ]);

        return $this;
    }
}
