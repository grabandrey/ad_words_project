<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cost extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should use updated_at timestamp.
     *
     * @var bool
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'amount',
        'generated_at',
        'budget_at_generation',
        'daily_limit_at_generation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'generated_at' => 'datetime',
        'budget_at_generation' => 'decimal:2',
        'daily_limit_at_generation' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns the cost.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the utilization percentage of daily limit.
     */
    public function getDailyUtilizationAttribute(): float
    {
        if ($this->daily_limit_at_generation == 0) {
            return 0;
        }

        return ($this->amount / $this->daily_limit_at_generation) * 100;
    }

    /**
     * Check if cost exceeded the budget at generation.
     */
    public function exceededBudget(): bool
    {
        return $this->amount > $this->budget_at_generation;
    }

    /**
     * Check if cost exceeded the daily limit (2x budget).
     */
    public function exceededDailyLimit(): bool
    {
        return $this->amount > $this->daily_limit_at_generation;
    }
}
