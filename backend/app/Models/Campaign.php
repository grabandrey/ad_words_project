<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'current_daily_budget',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_daily_budget' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the budget histories for the campaign.
     */
    public function budgetHistories(): HasMany
    {
        return $this->hasMany(BudgetHistory::class);
    }

    /**
     * Get the costs for the campaign.
     */
    public function costs(): HasMany
    {
        return $this->hasMany(Cost::class);
    }

    /**
     * Scope a query to only include active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include paused campaigns.
     */
    public function scopePaused($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Check if the campaign is paused.
     */
    public function isPaused(): bool
    {
        return !$this->is_active || $this->current_daily_budget == 0;
    }

    /**
     * Pause the campaign.
     */
    public function pause(): void
    {
        $this->update([
            'is_active' => false,
            'current_daily_budget' => 0,
        ]);
    }

    /**
     * Resume the campaign with a given budget.
     */
    public function resume(float $budget): void
    {
        $this->update([
            'is_active' => true,
            'current_daily_budget' => $budget,
        ]);
    }
}
