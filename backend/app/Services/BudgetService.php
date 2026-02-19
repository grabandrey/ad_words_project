<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\BudgetHistory;
use Carbon\Carbon;

class BudgetService
{
    /**
     * Update campaign budget.
     * The observer will automatically create budget history.
     *
     * @param Campaign $campaign
     * @param float $newBudget
     * @return Campaign
     */
    public function updateBudget(Campaign $campaign, float $newBudget): Campaign
    {
        $campaign->update([
            'current_daily_budget' => $newBudget,
        ]);

        return $campaign->fresh();
    }

    /**
     * Pause a campaign (set budget to 0 and mark inactive).
     *
     * @param Campaign $campaign
     * @return Campaign
     */
    public function pauseCampaign(Campaign $campaign): Campaign
    {
        $campaign->pause();

        return $campaign->fresh();
    }

    /**
     * Resume a campaign with a given budget.
     *
     * @param Campaign $campaign
     * @param float|null $budget
     * @return Campaign
     */
    public function resumeCampaign(Campaign $campaign, ?float $budget = null): Campaign
    {
        // If no budget provided, try to use last non-zero budget
        if ($budget === null) {
            $lastNonZeroBudget = $this->getLastNonZeroBudget($campaign);
            $budget = $lastNonZeroBudget ?? 100.00; // Default to 100 if no history
        }

        $campaign->resume($budget);

        return $campaign->fresh();
    }

    /**
     * Get the last non-zero budget from history.
     *
     * @param Campaign $campaign
     * @return float|null
     */
    protected function getLastNonZeroBudget(Campaign $campaign): ?float
    {
        $history = $campaign->budgetHistories()
            ->where('new_budget', '>', 0)
            ->orderByDesc('changed_at')
            ->first();

        return $history ? (float) $history->new_budget : null;
    }

    /**
     * Get budget history for a campaign within a date range.
     *
     * @param Campaign $campaign
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBudgetHistory(Campaign $campaign, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $query = $campaign->budgetHistories();

        if ($startDate) {
            $query->where('changed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('changed_at', '<=', $endDate);
        }

        return $query->orderBy('changed_at', 'desc')->get();
    }

    /**
     * Get budget at a specific time.
     *
     * @param Campaign $campaign
     * @param Carbon $timestamp
     * @return float
     */
    public function getBudgetAtTime(Campaign $campaign, Carbon $timestamp): float
    {
        $history = $campaign->budgetHistories()
            ->where('changed_at', '<=', $timestamp)
            ->orderByDesc('changed_at')
            ->first();

        return $history ? (float) $history->new_budget : 0;
    }
}
