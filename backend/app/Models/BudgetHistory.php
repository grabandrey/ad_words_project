<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetHistory extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campaign_id',
        'previous_budget',
        'new_budget',
        'changed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'previous_budget' => 'decimal:2',
        'new_budget' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the campaign that owns the budget history.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the budget change amount.
     */
    public function getBudgetChangeAttribute(): float
    {
        return $this->new_budget - $this->previous_budget;
    }

    /**
     * Check if budget was increased.
     */
    public function wasIncreased(): bool
    {
        return $this->new_budget > $this->previous_budget;
    }

    /**
     * Check if budget was decreased.
     */
    public function wasDecreased(): bool
    {
        return $this->new_budget < $this->previous_budget;
    }
}
