<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Cost;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CostGenerationService
{
    /**
     * Generate costs for a campaign over a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Number of costs generated
     */
    public function generateCostsForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): int
    {
        $totalGenerated = 0;
        $budgetHistory = $this->getBudgetHistoryForPeriod($campaign, $startDate, $endDate);

        // Iterate through each day in the period
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $day) {
            $generated = $this->generateCostsForDay($campaign, $day, $budgetHistory);
            $totalGenerated += $generated;
        }

        return $totalGenerated;
    }

    /**
     * Generate costs for a single day.
     *
     * @param Campaign $campaign
     * @param Carbon $day
     * @param Collection $budgetHistory
     * @return int Number of costs generated for the day
     */
    protected function generateCostsForDay(Campaign $campaign, Carbon $day, Collection $budgetHistory): int
    {
        $generated = 0;

        // Check if there's a budget for this day
        $dayStartBudget = $this->getBudgetAtTime($budgetHistory, $day->copy()->startOfDay());

        if ($dayStartBudget == 0) {
            return 0; // Campaign paused or no budget
        }

        // Randomly determine cost generation count (1-10 per day)
        $costCount = rand(1, 10);

        // Generate random timestamps throughout the day
        $timestamps = $this->generateRandomTimestamps($day, $costCount);

        // Sort timestamps chronologically
        sort($timestamps);

        $dailyCumulativeCost = 0;

        foreach ($timestamps as $timestamp) {
            // Get budget at this specific timestamp
            $currentBudget = $this->getBudgetAtTime($budgetHistory, Carbon::parse($timestamp));

            if ($currentBudget == 0) {
                continue; // Campaign paused at this time
            }

            // Calculate max allowed cost at this moment (Rule 1: 2x budget)
            $dailyMaxAtTimestamp = $currentBudget * 2;
            $remainingDailyCapacity = $dailyMaxAtTimestamp - $dailyCumulativeCost;

            if ($remainingDailyCapacity <= 0) {
                continue; // Already hit daily limit
            }

            // Check monthly constraint (Rule 2: sum of max daily budgets)
            $monthStart = Carbon::parse($timestamp)->startOfMonth();
            $monthCumulativeCost = $this->getMonthlyCumulativeCost($campaign, $monthStart, Carbon::parse($timestamp));
            $monthMaxBudget = $this->calculateMonthMaxBudget($budgetHistory, $monthStart, Carbon::parse($timestamp));
            $remainingMonthlyCapacity = $monthMaxBudget - $monthCumulativeCost;

            if ($remainingMonthlyCapacity <= 0) {
                continue; // Monthly limit reached
            }

            // Generate cost respecting both constraints
            $maxPossibleCost = min($remainingDailyCapacity, $remainingMonthlyCapacity);

            // Skip if max possible cost is too small
            if ($maxPossibleCost < 0.01) {
                continue;
            }

            // Generate random cost (10% to 100% of available capacity)
            $minCost = $maxPossibleCost * 0.1;
            $costAmount = $minCost + (mt_rand() / mt_getrandmax()) * ($maxPossibleCost - $minCost);

            // Round to 2 decimal places
            $costAmount = round($costAmount, 2);

            // Skip if rounded cost is zero
            if ($costAmount <= 0) {
                continue;
            }

            // Create cost record
            Cost::create([
                'campaign_id' => $campaign->id,
                'amount' => $costAmount,
                'generated_at' => $timestamp,
                'budget_at_generation' => $currentBudget,
                'daily_limit_at_generation' => $dailyMaxAtTimestamp,
            ]);

            $dailyCumulativeCost += $costAmount;
            $generated++;
        }

        return $generated;
    }

    /**
     * Get budget value at a specific timestamp.
     *
     * @param Collection $budgetHistory
     * @param Carbon $timestamp
     * @return float
     */
    protected function getBudgetAtTime(Collection $budgetHistory, Carbon $timestamp): float
    {
        // Find the most recent budget change before or at timestamp
        $relevantHistory = $budgetHistory->filter(function ($history) use ($timestamp) {
            return Carbon::parse($history->changed_at)->lte($timestamp);
        })->sortByDesc('changed_at');

        if ($relevantHistory->isEmpty()) {
            return 0;
        }

        return (float) $relevantHistory->first()->new_budget;
    }

    /**
     * Calculate max monthly cost: sum of max daily budgets for each day.
     *
     * @param Collection $budgetHistory
     * @param Carbon $monthStart
     * @param Carbon $currentDate
     * @return float
     */
    protected function calculateMonthMaxBudget(Collection $budgetHistory, Carbon $monthStart, Carbon $currentDate): float
    {
        $total = 0;

        // For each day in the month up to current date
        $period = CarbonPeriod::create($monthStart, $currentDate->copy()->startOfDay());

        foreach ($period as $day) {
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            // Find all budget values active during this day
            $dayBudgets = $budgetHistory->filter(function ($history) use ($dayEnd) {
                return Carbon::parse($history->changed_at)->lte($dayEnd);
            })->pluck('new_budget');

            // Get the maximum budget for the day
            $maxDayBudget = $dayBudgets->isEmpty() ? 0 : $dayBudgets->max();
            $total += $maxDayBudget;
        }

        return $total;
    }

    /**
     * Get cumulative cost for a specific day.
     *
     * @param Campaign $campaign
     * @param Carbon $day
     * @return float
     */
    protected function getDailyCumulativeCost(Campaign $campaign, Carbon $day): float
    {
        return (float) $campaign->costs()
            ->whereDate('generated_at', $day->toDateString())
            ->sum('amount');
    }

    /**
     * Get cumulative cost for a month up to a specific date.
     *
     * @param Campaign $campaign
     * @param Carbon $monthStart
     * @param Carbon $upToDate
     * @return float
     */
    protected function getMonthlyCumulativeCost(Campaign $campaign, Carbon $monthStart, Carbon $upToDate): float
    {
        return (float) $campaign->costs()
            ->whereBetween('generated_at', [$monthStart, $upToDate])
            ->sum('amount');
    }

    /**
     * Generate random timestamps throughout a day.
     *
     * @param Carbon $day
     * @param int $count
     * @return array
     */
    protected function generateRandomTimestamps(Carbon $day, int $count): array
    {
        $timestamps = [];
        $dayStart = $day->copy()->startOfDay();

        for ($i = 0; $i < $count; $i++) {
            $randomSeconds = rand(0, 86399); // seconds in a day
            $timestamp = $dayStart->copy()->addSeconds($randomSeconds);
            $timestamps[] = $timestamp->toDateTimeString();
        }

        // Remove duplicates
        return array_unique($timestamps);
    }

    /**
     * Get budget history for a campaign within a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getBudgetHistoryForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): Collection
    {
        // Get all budget histories up to end date
        // We need history before start date to know the budget at start
        return $campaign->budgetHistories()
            ->where('changed_at', '<=', $endDate)
            ->orderBy('changed_at')
            ->get();
    }

    /**
     * Clear all costs for a campaign within a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Number of costs deleted
     */
    public function clearCostsForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): int
    {
        return $campaign->costs()
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->delete();
    }
}
