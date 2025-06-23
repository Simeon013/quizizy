<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
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
        'total_questions_answered',
        'correct_answers',
        'xp',
        'level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_questions_answered' => 'integer',
        'correct_answers' => 'integer',
        'xp' => 'integer',
        'level' => 'integer',
    ];

    /**
     * The number of XP needed to level up.
     *
     * @var int
     */
    protected $xpToNextLevel = 1000;

    /**
     * The XP multiplier for leveling up.
     *
     * @var float
     */
    protected $xpMultiplier = 1.2;

    /**
     * Get the user that owns the stats.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that the stats belong to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate the accuracy percentage.
     */
    public function getAccuracyAttribute(): float
    {
        if ($this->total_questions_answered === 0) {
            return 0;
        }

        return round(($this->correct_answers / $this->total_questions_answered) * 100, 2);
    }

    /**
     * Calculate the XP needed for the next level.
     */
    public function getXpToNextLevelAttribute(): int
    {
        return (int) ($this->xpToNextLevel * (pow($this->xpMultiplier, $this->level - 1)));
    }

    /**
     * Add XP to the user's stats.
     */
    public function addXp(int $xpEarned): void
    {
        $this->xp += $xpEarned;
        $this->user->total_xp += $xpEarned;
        
        // Check for level up
        while ($this->xp >= $this->xpToNextLevel) {
            $this->level++;
            $this->user->level++;
            $this->xp -= $this->xpToNextLevel;
        }
        
        $this->save();
        $this->user->save();
    }

    /**
     * Record a correct answer.
     */
    public function recordCorrectAnswer(int $xpEarned = 10): void
    {
        $this->total_questions_answered++;
        $this->correct_answers++;
        $this->addXp($xpEarned);
    }

    /**
     * Record an incorrect answer.
     */
    public function recordIncorrectAnswer(int $xpEarned = 2): void
    {
        $this->total_questions_answered++;
        $this->addXp($xpEarned);
    }

    /**
     * Get the user's rank based on XP.
     */
    public function getRankAttribute(): int
    {
        return static::where('category_id', $this->category_id)
            ->where('xp', '>', $this->xp)
            ->count() + 1;
    }
}
